<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Enums\MessageDeliveryStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReceipt;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RealtimeChatStorageTest extends TestCase
{
    use RefreshDatabase;

    private function message(): Message
    {
        $sender = User::factory()->create();
        $conversation = Conversation::create(['public_id' => (string) Str::uuid()]);
        $conversation->participants()->attach($sender);

        return $conversation->messages()->create(['sender_id' => $sender->id, 'type' => 'text', 'body' => 'Hola']);
    }

    public function test_upgrade_and_rollback_preserve_legacy_messages_and_participants(): void
    {
        $migration = require database_path('migrations/2026_09_21_000000_prepare_realtime_chat_storage.php');
        $migration->down();
        try {
            $message = $this->message();
            $message->update(['attachment_path' => 'legacy/image.jpg']);
        } finally {
            $migration->up();
        }
        $this->assertNull($message->fresh()->client_message_id);
        $this->assertSame('legacy/image.jpg', $message->fresh()->attachment_path);
        $this->assertCount(1, $message->conversation->participants);
        $migration->down();
        try {
            $this->assertDatabaseHas('messages', ['id' => $message->id, 'body' => 'Hola']);
            $this->assertFalse(Schema::hasTable('message_receipts'));
            $this->assertFalse(Schema::hasColumn('messages', 'client_message_id'));
        } finally {
            $migration->up();
        }
    }

    public function test_publication_alias_and_latest_message_use_existing_tables(): void
    {
        $first = $this->message();
        $post = Post::create(['user_id' => $first->sender_id, 'type' => 'offer', 'body' => 'Oferta local']);
        $conversation = $first->conversation;
        $conversation->update(['post_id' => $post->id]);
        $latest = $conversation->messages()->create(['sender_id' => $first->sender_id, 'body' => 'Siguiente']);
        $conversation->load(['publication', 'post', 'latestMessage', 'participants']);
        $this->assertTrue($conversation->publication->is($post));
        $this->assertTrue($conversation->post->is($post));
        $this->assertTrue($conversation->latestMessage->is($latest));
        $this->assertTrue($post->conversations->first()->is($conversation));
        $post->delete();
        $this->assertNull($conversation->fresh()->post_id);
        $this->assertDatabaseHas('messages', ['id' => $first->id]);
    }

    public function test_attachment_metadata_is_private_and_relations_are_available(): void
    {
        $message = $this->message();
        $attachment = $message->attachments()->create([
            'path' => 'chat/private.jpg', 'original_name' => 'producto.jpg', 'mime_type' => 'image/jpeg',
            'size_bytes' => 1024, 'width' => 640, 'height' => 480,
        ]);
        $this->assertTrue(Str::isUuid($attachment->public_id));
        $this->assertSame('public_id', $attachment->getRouteKeyName());
        $this->assertSame('local', $attachment->disk);
        $this->assertSame(1024, $attachment->fresh()->size_bytes);
        $this->assertTrue($attachment->message->is($message));
        $this->assertArrayNotHasKey('path', $attachment->toArray());
        $this->assertArrayNotHasKey('disk', $attachment->toArray());
    }

    public function test_delivery_and_read_are_per_recipient_not_per_conversation(): void
    {
        $message = $this->message();
        $recipients = User::factory()->count(2)->create();
        $message->conversation->participants()->attach($recipients->modelKeys());
        $receipt = $message->receipts()->create(['user_id' => $recipients[0]->id]);
        $other = $message->receipts()->create(['user_id' => $recipients[1]->id]);
        $this->assertSame(MessageDeliveryStatus::Sent, $receipt->deliveryStatus());
        $receipt->update(['delivered_at' => now()]);
        $this->assertSame(MessageDeliveryStatus::Delivered, $receipt->fresh()->deliveryStatus());
        $receipt->update(['read_at' => now()]);
        $this->assertSame(MessageDeliveryStatus::Read, $receipt->fresh()->deliveryStatus());
        $this->assertSame(MessageDeliveryStatus::Sent, $other->fresh()->deliveryStatus());
        $this->assertTrue($receipt->user->is($recipients[0]));
        $this->assertTrue($receipt->message->is($message));
    }

    public function test_duplicate_receipt_is_rejected_by_database(): void
    {
        $message = $this->message();
        $recipient = User::factory()->create();
        $message->receipts()->create(['user_id' => $recipient->id]);
        $this->expectException(QueryException::class);
        $message->receipts()->create(['user_id' => $recipient->id]);
    }

    public function test_duplicate_client_message_token_is_rejected_for_same_sender_and_chat(): void
    {
        $message = $this->message();
        $token = (string) Str::uuid();
        $message->update(['client_message_id' => $token]);
        $this->expectException(QueryException::class);
        $message->conversation->messages()->create(['sender_id' => $message->sender_id, 'client_message_id' => $token, 'body' => 'Reintento']);
    }

    public function test_deleting_message_cascades_metadata_and_receipts(): void
    {
        $message = $this->message();
        $message->attachments()->create(['path' => 'chat/test.png', 'original_name' => 'test.png', 'mime_type' => 'image/png', 'size_bytes' => 10]);
        $message->receipts()->create(['user_id' => User::factory()->create()->id]);
        $message->delete();
        $this->assertSame(0, MessageAttachment::count());
        $this->assertSame(0, MessageReceipt::count());
    }
}
