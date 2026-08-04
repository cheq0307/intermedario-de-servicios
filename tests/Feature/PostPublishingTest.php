<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_provider_can_publish_a_service_with_a_structured_price(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);

        $response = $this->actingAs($provider)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'service',
            'title' => 'Instalación eléctrica',
            'price_type' => 'starting_at',
            'price' => '850.50',
            'body' => 'Realizo instalaciones eléctricas dentro de la comunidad.',
        ]);

        $response
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('listings', [
            'type' => 'service',
            'name' => 'Instalación eléctrica',
            'price_type' => 'starting_at',
            'price_amount' => 85050,
        ]);
        $this->assertDatabaseHas('posts', [
            'user_id' => $provider->id,
            'type' => 'service',
        ]);
    }

    public function test_a_provider_can_publish_a_product_with_stock(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);

        $this->actingAs($provider)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'product',
            'title' => 'Paquete de hojas blancas',
            'price_type' => 'fixed',
            'price' => '95',
            'stock' => 12,
            'body' => 'Paquete de quinientas hojas disponible para entrega local.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('listings', [
            'type' => 'product',
            'price_amount' => 9500,
            'stock' => 12,
        ]);
    }

    public function test_a_client_can_publish_a_structured_job_request(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'job_request',
            'title' => 'Reparar una fuga de agua',
            'budget_min' => '300',
            'budget_max' => '700.50',
            'urgency' => 'soon',
            'location_label' => 'Barrio del centro',
            'body' => 'Busco plomero para revisar una fuga durante esta semana.',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('job_requests', [
            'client_id' => $client->id,
            'title' => 'Reparar una fuga de agua',
            'budget_min_amount' => 30000,
            'budget_max_amount' => 70050,
            'status' => 'published',
        ]);
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
                'submission_token' => (string) Str::uuid(),
                'type' => 'product',
                'body' => 'Estoy intentando publicar un producto como cliente.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('type');
    }

    public function test_repeated_submission_token_creates_only_one_request(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $token = (string) Str::uuid();
        $payload = [
            'submission_token' => $token,
            'type' => 'job_request',
            'title' => 'Necesito reparar una tubería',
            'budget_min' => '500',
            'budget_max' => '1000',
            'urgency' => 'urgent',
            'location_label' => 'Zona centro',
            'body' => 'La tubería debajo del fregadero tiene una fuga constante.',
        ];

        $this->actingAs($client)
            ->post(route('posts.store'), $payload)
            ->assertRedirect(route('dashboard'));

        $this->actingAs($client)
            ->post(route('posts.store'), $payload)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'La publicación ya había sido procesada; no se creó un duplicado.');

        $this->assertDatabaseCount('job_requests', 1);
        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['submission_token' => $token]);
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
