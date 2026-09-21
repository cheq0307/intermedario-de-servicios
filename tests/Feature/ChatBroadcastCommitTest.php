<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatBroadcastCommitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_broadcast_waits_for_commit_and_is_discarded_on_rollback(): void
    {
        Event::fake([MessageSent::class]);
        $users = User::factory()->count(2)->create();
        $chat = Conversation::create(['public_id' => (string) Str::uuid()]);
        $chat->participants()->attach($users->modelKeys());
        DB::beginTransaction();
        app(ChatService::class)->send($chat, $users[0], 'No sale', (string) Str::uuid());
        Event::assertNotDispatched(MessageSent::class);
        DB::rollBack();
        Event::assertNotDispatched(MessageSent::class);
        $this->assertDatabaseCount('messages', 0);
        $this->assertDatabaseCount('notifications', 0);
        DB::beginTransaction();
        app(ChatService::class)->send($chat, $users[0], 'Confirmado', (string) Str::uuid());
        Event::assertNotDispatched(MessageSent::class);
        DB::commit();
        Event::assertDispatchedTimes(MessageSent::class, 1);
    }
}
