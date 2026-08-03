<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_provider_can_publish_a_service(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);

        $response = $this->actingAs($provider)->post(route('posts.store'), [
            'type' => 'service',
            'body' => 'Realizo instalaciones eléctricas dentro de la comunidad.',
        ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('posts', [
            'user_id' => $provider->id,
            'type' => 'service',
        ]);
    }

    public function test_a_client_can_publish_a_job_request(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)->post(route('posts.store'), [
            'type' => 'job_request',
            'body' => 'Busco plomero para revisar una fuga durante esta semana.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('posts', [
            'user_id' => $client->id,
            'type' => 'job_request',
        ]);
    }

    public function test_a_client_cannot_publish_a_provider_offer(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)
            ->from(route('dashboard'))
            ->post(route('posts.store'), [
                'type' => 'product',
                'body' => 'Estoy intentando publicar un producto como cliente.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('type');
    }

    public function test_published_posts_appear_in_the_feed(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        Post::create([
            'user_id' => $provider->id,
            'type' => 'promotion',
            'body' => 'Promoción especial disponible solamente durante esta semana.',
            'published_at' => now(),
        ]);

        $this->actingAs($provider)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Promoción especial disponible');
    }
}
