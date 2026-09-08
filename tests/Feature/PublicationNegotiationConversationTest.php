<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicationNegotiationConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_post_has_an_independent_chat_for_the_same_two_users(): void
    {
        $owner = User::factory()->create();
        $interested = User::factory()->create();
        $firstPost = $this->postBy($owner, 'Primera publicación');
        $secondPost = $this->postBy($owner, 'Segunda publicación');

        $this->actingAs($interested, 'web')->post(route('posts.conversations.start', $firstPost))->assertRedirect();
        $this->actingAs($interested, 'web')->post(route('posts.conversations.start', $firstPost))->assertRedirect();
        $this->assertSame(1, Conversation::where('type', 'negotiation')->count());

        $this->actingAs($interested, 'web')->post(route('posts.conversations.start', $secondPost))->assertRedirect();
        $this->actingAs($interested, 'web')->post(route('conversations.start'), ['recipient_id' => $owner->id])->assertRedirect();

        $this->assertSame(2, Conversation::where('type', 'negotiation')->count());
        $this->assertSame(1, Conversation::where('type', 'direct')->count());
        $this->assertDatabaseHas('conversations', ['post_id' => $firstPost->id, 'type' => 'negotiation']);
        $this->assertDatabaseHas('conversations', ['post_id' => $secondPost->id, 'type' => 'negotiation']);
    }

    public function test_participant_can_extend_once_and_then_close_chat_as_read_only(): void
    {
        $owner = User::factory()->create();
        $interested = User::factory()->create();
        $post = $this->postBy($owner, 'Servicio local');
        $this->actingAs($interested, 'web')->post(route('posts.conversations.start', $post));
        $conversation = Conversation::where('type', 'negotiation')->firstOrFail();
        $originalExpiry = $conversation->expires_at;

        $this->actingAs($owner, 'web')
            ->patch(route('conversations.extend', $conversation))
            ->assertStatus(422);

        $conversation->update(['expires_at' => now()->addDays(2)->subMinute()]);
        $originalExpiry = $conversation->fresh()->expires_at;

        $this->actingAs($owner, 'web')
            ->patch(route('conversations.extend', $conversation))
            ->assertRedirect();

        $conversation->refresh();
        $this->assertSame(1, $conversation->extension_count);
        $this->assertSame(7.0, $originalExpiry->diffInDays($conversation->expires_at));

        $this->actingAs($interested, 'web')
            ->patch(route('conversations.extend', $conversation))
            ->assertStatus(422);

        $this->actingAs($interested, 'web')
            ->patch(route('conversations.close', $conversation))
            ->assertRedirect();

        $conversation->refresh();
        $this->assertSame('archived', $conversation->state);
        $this->assertSame('user_closed', $conversation->closed_reason);
        $this->assertNotNull($conversation->retention_until);

        $this->actingAs($owner, 'web')
            ->post(route('conversations.messages.store', $conversation), ['body' => 'Ya no debe enviarse'])
            ->assertStatus(422);

        $this->actingAs($interested, 'web')
            ->post(route('posts.conversations.start', $post))
            ->assertRedirect();

        $newConversation = Conversation::where('type', 'negotiation')->where('state', 'active')->firstOrFail();
        $this->assertNotSame($conversation->id, $newConversation->id);
        $this->assertSame(2, Conversation::where('type', 'negotiation')->count());
        $this->assertSame('archived', $conversation->fresh()->state);
    }

    public function test_command_expires_inactive_chat_and_purges_it_after_retention(): void
    {
        $owner = User::factory()->create();
        $interested = User::factory()->create();
        $post = $this->postBy($owner, 'Conversación vencida');
        $this->actingAs($interested, 'web')->post(route('posts.conversations.start', $post));
        $conversation = Conversation::where('type', 'negotiation')->firstOrFail();
        $conversation->update(['expires_at' => now()->subMinute()]);

        $this->artisan('plaza:maintain-conversations')->assertSuccessful();
        $conversation->refresh();
        $this->assertSame('archived', $conversation->state);
        $this->assertSame('expired', $conversation->closed_reason);
        $this->assertTrue($conversation->retention_until->isAfter(now()->addMonths(2)));

        $conversation->update(['retention_until' => now()->subSecond()]);
        $this->artisan('plaza:maintain-conversations')->assertSuccessful();
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_outsider_cannot_extend_or_close_negotiation(): void
    {
        $owner = User::factory()->create();
        $interested = User::factory()->create();
        $outsider = User::factory()->create();
        $post = $this->postBy($owner, 'Conversación privada');
        $this->actingAs($interested, 'web')->post(route('posts.conversations.start', $post));
        $conversation = Conversation::where('type', 'negotiation')->firstOrFail();

        $this->actingAs($outsider, 'web')->patch(route('conversations.extend', $conversation))->assertForbidden();
        $this->actingAs($outsider, 'web')->patch(route('conversations.close', $conversation))->assertForbidden();
    }

    private function postBy(User $owner, string $body): Post
    {
        return Post::create([
            'user_id' => $owner->id,
            'type' => 'job_request',
            'body' => $body,
            'published_at' => now(),
        ]);
    }
}
