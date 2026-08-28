<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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
        $this->assertSame('Nuevo seguidor', $profile->notifications()->firstOrFail()->data['title']);

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

    public function test_administrative_view_of_a_public_profile_is_read_only(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $profile = User::factory()->create();

        $this->actingAs($admin)->get(route('profile.show', $profile))
            ->assertOk()
            ->assertSee('Vista administrativa de solo lectura')
            ->assertSee(route('admin.users.show', $profile), false)
            ->assertDontSee('>Seguir</button>', false)
            ->assertDontSee('Contactar dentro de Plaza Local');

        $this->actingAs($admin)
            ->post(route('profiles.follow.toggle', $profile))
            ->assertStatus(422);

        $this->actingAs($admin)
            ->post(route('conversations.start'), ['recipient_id' => $profile->id])
            ->assertStatus(422);
    }

    public function test_more_screen_groups_account_tools_without_repeating_profile_in_bottom_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('more.index'))
            ->assertOk()
            ->assertSee('Mis publicaciones')
            ->assertSee('Mis pedidos y trabajos')
            ->assertSee('Empleo')
            ->assertSee('href="'.route('vacancies.index').'"', false)
            ->assertSee('Ayuda y soporte')
            ->assertSee('Cerrar sesión')
            ->assertSee('Más');
    }
}
