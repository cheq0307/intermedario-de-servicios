<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_start_only_one_direct_conversation_with_another_user(): void
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)->post(route('conversations.start'), ['recipient_id' => $recipient->id])->assertRedirect();
        $this->actingAs($sender)->post(route('conversations.start'), ['recipient_id' => $recipient->id])->assertRedirect();

        $this->assertDatabaseCount('conversations', 1);
        $conversation = Conversation::firstOrFail();
        $this->assertCount(2, $conversation->participants);
    }

    public function test_participant_can_send_and_read_messages(): void
    {
        [$sender, $recipient, $conversation] = $this->directConversation();

        $this->actingAs($sender)
            ->post(route('conversations.messages.store', $conversation), ['body' => 'Hola, ¿sigues disponible?'])
            ->assertRedirect(route('conversations.show', $conversation).'#ultimo-mensaje');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Hola, ¿sigues disponible?',
        ]);

        $this->actingAs($recipient)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->assertSee('Hola, ¿sigues disponible?');
    }

    public function test_outsider_cannot_read_or_write_conversation(): void
    {
        [, , $conversation] = $this->directConversation();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->get(route('conversations.show', $conversation))->assertForbidden();
        $this->actingAs($outsider)->post(route('conversations.messages.store', $conversation), ['body' => 'Intrusión'])->assertForbidden();
    }

    public function test_unverified_user_cannot_start_conversation(): void
    {
        $sender = User::factory()->unverified()->create();
        $recipient = User::factory()->create();

        $this->actingAs($sender)
            ->post(route('conversations.start'), ['recipient_id' => $recipient->id])
            ->assertRedirect(route('verification.notice'));
    }

    private function directConversation(): array
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $this->actingAs($sender)->post(route('conversations.start'), ['recipient_id' => $recipient->id]);

        return [$sender, $recipient, Conversation::firstOrFail()];
    }
}
