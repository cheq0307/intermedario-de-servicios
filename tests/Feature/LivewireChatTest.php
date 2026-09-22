<?php

namespace Tests\Feature;

use App\Livewire\Chat\ChatWindow;
use App\Livewire\Chat\ConversationList;
use App\Livewire\Chat\MessageInput;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireChatTest extends TestCase
{
    use RefreshDatabase;

    private function chat(): array
    {
        $users = User::factory()->count(2)->create();
        $post = Post::create(['user_id' => $users[0]->id, 'type' => 'offer', 'body' => 'Tacos para eventos', 'published_at' => now()]);
        $chat = Conversation::create(['public_id' => (string) Str::uuid(), 'post_id' => $post->id]);
        $chat->participants()->attach($users->modelKeys());

        return [$chat, $users[0], $users[1]];
    }

    public function test_list_searches_context_without_exposing_other_conversations(): void
    {
        [$chat, $sender, $recipient] = $this->chat();
        $chat->messages()->create(['sender_id' => $sender->id, 'body' => 'Último saludo']);
        [$private, $outsider] = $this->chat();
        $private->post->update(['body' => 'Información ajena']);
        Livewire::actingAs($recipient, 'web')->test(ConversationList::class)
            ->assertSee('Tacos para eventos')->assertSee('Último saludo')->assertDontSee('Información ajena')
            ->set('search', 'Tacos')->assertSee('Último saludo')
            ->set('search', 'No coincide')->assertDontSee('Último saludo');
    }

    public function test_history_pages_do_not_mark_read_until_visible_ack(): void
    {
        [$chat, $sender, $recipient] = $this->chat();
        for ($i = 1; $i <= 65; $i++) {
            $chat->messages()->create(['sender_id' => $sender->id, 'body' => sprintf('Mensaje #%03d', $i)]);
        }
        $component = Livewire::actingAs($recipient, 'web')->test(ChatWindow::class, ['conversationId' => $chat->public_id])
            ->assertSee('Mensaje #065')->assertDontSee('Mensaje #001')->call('loadOlder')->assertSee('Mensaje #006')
            ->call('loadOlder')->assertSee('Mensaje #001');
        $this->assertNull($chat->participants()->find($recipient->id)->pivot->last_read_message_id);
        $component->call('acknowledge', $chat->messages()->max('id'), true);
        $this->assertSame(0, $recipient->unreadConversationsCount());
    }

    public function test_livewire_send_is_idempotent_and_notifies_siblings(): void
    {
        [$chat, $sender] = $this->chat();
        $token = (string) Str::uuid();
        Livewire::actingAs($sender, 'web')->test(MessageInput::class, ['conversationId' => $chat->public_id])
            ->call('send', 'Hola desde Livewire', $token)->assertDispatched('chat-message-sent')->assertDispatched('chat-updated')
            ->call('send', 'Hola desde Livewire', $token);
        $this->assertSame(1, $chat->messages()->count());
    }

    public function test_livewire_reauthorizes_after_participant_is_removed(): void
    {
        [$chat, $sender] = $this->chat();
        $component = Livewire::actingAs($sender, 'web')->test(ChatWindow::class, ['conversationId' => $chat->public_id]);
        $chat->participants()->detach($sender);
        $component->call('refreshMessages')->assertForbidden();
    }

    public function test_outsider_cannot_mount_or_download_publication_context(): void
    {
        [$chat] = $this->chat();
        $outsider = User::factory()->create();
        Livewire::actingAs($outsider, 'web')->test(ChatWindow::class, ['conversationId' => $chat->public_id])->assertForbidden();
        $this->actingAs($outsider, 'web')->get(route('chat.publication', $chat))->assertForbidden();
    }

    public function test_closure_is_checked_again_when_sending(): void
    {
        [$chat, $sender] = $this->chat();
        $component = Livewire::actingAs($sender, 'web')->test(MessageInput::class, ['conversationId' => $chat->public_id]);
        $chat->update(['state' => 'archived']);
        $component->call('send', 'No permitido', (string) Str::uuid())->assertStatus(422);
        $this->assertSame(0, $chat->messages()->count());
    }
}
