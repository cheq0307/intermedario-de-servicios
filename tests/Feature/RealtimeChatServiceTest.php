<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\MessageReceipt;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RealtimeChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private function chat(): array
    {
        $users = User::factory()->count(2)->create();
        $conversation = Conversation::create(['public_id' => (string) Str::uuid()]);
        $conversation->participants()->attach($users->modelKeys());

        return [$conversation, $users[0], $users[1], app(ChatService::class)];
    }

    public function test_retry_creates_one_message_receipt_and_notification(): void
    {
        [$chat, $sender, $recipient, $service] = $this->chat();
        $token = (string) Str::uuid();
        $message = $service->send($chat, $sender, 'Hola', $token);
        $again = $service->send($chat, $sender, 'Hola', $token);
        $this->assertTrue($message->is($again));
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseCount('message_receipts', 1);
        $this->assertSame(1, $recipient->unreadNotifications()->count());
        $this->assertNull($message->receipts()->first()->read_at);
        $event = new MessageSent($chat->public_id, $message->id, [$sender->id, $recipient->id]);
        $this->assertSame(['conversation_id', 'message_id'], array_keys($event->broadcastWith()));
        foreach ($event->broadcastOn() as $channel) {
            $this->assertStringStartsWith('private-chat.', $channel->name);
        }
    }

    public function test_only_participants_can_send(): void
    {
        [$chat, , , $service] = $this->chat();
        $this->expectException(AuthorizationException::class);
        $service->send($chat, User::factory()->create(), 'Espía', (string) Str::uuid());
    }

    public function test_delivery_is_not_read_and_read_ack_is_monotonic(): void
    {
        [$chat, $sender, $recipient, $service] = $this->chat();
        $first = $service->send($chat, $sender, 'Uno', (string) Str::uuid());
        $second = $service->send($chat, $sender, 'Dos', (string) Str::uuid());
        $service->acknowledge($chat, $recipient, $second->id, false);
        $this->assertNull($second->receipts()->first()->read_at);
        $this->assertNotNull($second->receipts()->first()->delivered_at);
        $service->acknowledge($chat, $recipient, $second->id, true);
        $readAt = $second->receipts()->first()->read_at;
        $this->travel(1)->minutes();
        $service->acknowledge($chat, $recipient, $first->id, true);
        $this->assertTrue($readAt->eq($second->receipts()->first()->read_at));
        $this->assertSame($second->id, $chat->participants()->find($recipient->id)->pivot->last_read_message_id);
        $this->assertSame(0, $recipient->unreadNotifications()->count());
    }

    public function test_viewing_suppresses_bell_but_never_fakes_read(): void
    {
        [$chat, $sender, $recipient, $service] = $this->chat();
        $service->viewing($chat, $recipient, true);
        $message = $service->send($chat, $sender, 'Hola', (string) Str::uuid());
        $this->assertSame(0, $recipient->notifications()->count());
        $this->assertNull($message->receipts()->first()->read_at);
        $service->viewing($chat, $recipient, false);
        $service->send($chat, $sender, 'Otro', (string) Str::uuid());
        $this->assertSame(1, $recipient->notifications()->count());
    }

    public function test_private_channel_auth_allows_member_and_rejects_outsider_and_other_inbox(): void
    {
        [$chat, $sender, $recipient] = $this->chat();
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret', 'broadcasting.connections.reverb.app_id' => 'test-app']);
        require base_path('routes/channels.php');
        $this->actingAs($sender, 'web')->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.conversations.'.$chat->public_id])->assertOk();
        $this->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.users.'.$recipient->id])->assertForbidden();
        $this->actingAs(User::factory()->create(), 'web')->postJson('/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.conversations.'.$chat->public_id])->assertForbidden();
    }

    public function test_image_download_is_private(): void
    {
        Storage::fake('local');
        [$chat, $sender, $recipient, $service] = $this->chat();
        $message = $service->send($chat, $sender, '', (string) Str::uuid(), [UploadedFile::fake()->image('producto.jpg', 100, 100)]);
        $attachment = $message->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);
        $this->assertSame('image/png', $attachment->mime_type);
        $this->actingAs($recipient, 'web')->get(route('chat.attachments.show', $attachment))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create(), 'web')->get(route('chat.attachments.show', $attachment))->assertForbidden();
    }

    public function test_ack_cannot_target_a_message_from_another_conversation(): void
    {
        [$chat, $sender, $recipient, $service] = $this->chat();
        [$other, $otherSender] = $this->chat();
        $message = $service->send($other, $otherSender, 'Privado', (string) Str::uuid());
        try {
            $service->acknowledge($chat, $recipient, $message->id, true);
            $this->fail('Expected unknown message.');
        } catch (ModelNotFoundException) {
            $this->assertFalse(MessageReceipt::where('message_id', $message->id)->where('user_id', $recipient->id)->exists());
        }
    }
}
