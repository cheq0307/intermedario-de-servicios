<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileFollowingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_follow_and_unfollow_another_marketplace_user(): void
    {
        $follower = User::factory()->create();
        $profile = User::factory()->create();

        $this->actingAs($follower)->post(route('profiles.follow.toggle', $profile))->assertRedirect();
        $this->assertDatabaseHas('user_follows', ['follower_id' => $follower->id, 'followed_id' => $profile->id]);

        $this->actingAs($follower)->post(route('profiles.follow.toggle', $profile))->assertRedirect();
        $this->assertDatabaseMissing('user_follows', ['follower_id' => $follower->id, 'followed_id' => $profile->id]);
    }

    public function test_a_user_cannot_follow_themselves(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('profiles.follow.toggle', $user))->assertStatus(422);
    }

    public function test_public_profile_displays_real_social_totals(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $post = Post::create(['user_id' => $owner->id, 'type' => 'job_request', 'body' => 'Servicio local publicado.', 'published_at' => now()]);
        PostReaction::create(['post_id' => $post->id, 'user_id' => $viewer->id, 'type' => 'like']);
        PostComment::create(['post_id' => $post->id, 'user_id' => $viewer->id, 'body' => 'Excelente']);
        PostShare::create(['post_id' => $post->id, 'user_id' => $viewer->id, 'channel' => 'native']);
        $viewer->following()->attach($owner);

        $this->get(route('profile.show', $owner))
            ->assertOk()
            ->assertSee('Seguidores')
            ->assertSee('Me gusta')
            ->assertSee('Comentarios')
            ->assertSee('Compartidos');
    }
}