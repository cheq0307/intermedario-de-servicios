<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_provider_can_publish_a_service_with_a_structured_price(): void
    {
        $provider = $this->approvedProvider();

        $response = $this->actingAs($provider)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'service',
            'category_id' => $this->categoryId(),
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
        $provider = $this->approvedProvider();

        $this->actingAs($provider)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'product',
            'category_id' => $this->categoryId(),
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

    public function test_an_approved_provider_can_publish_more_than_one_offer(): void
    {
        $provider = $this->approvedProvider();

        foreach (['Primera reparación', 'Segunda reparación'] as $title) {
            $this->actingAs($provider)->post(route('posts.store'), [
                'submission_token' => (string) Str::uuid(),
                'type' => 'service',
                'category_id' => $this->categoryId(),
                'title' => $title,
                'price_type' => 'quote',
                'body' => 'Servicio distinto publicado por la misma cuenta para la comunidad.',
            ])->assertRedirect(route('dashboard'));
        }

        $this->assertDatabaseCount('listings', 2);
        $this->assertDatabaseCount('posts', 2);
    }

    public function test_a_provider_account_can_also_publish_more_than_one_request(): void
    {
        $provider = $this->approvedProvider();

        foreach (['Necesito apoyo para una entrega', 'Necesito apoyo para una instalación'] as $title) {
            $this->actingAs($provider)->post(route('posts.store'), [
                'submission_token' => (string) Str::uuid(),
                'type' => 'job_request',
                'category_id' => $this->categoryId(),
                'title' => $title,
                'urgency' => 'normal',
                'body' => 'Esta solicitud demuestra que una sola cuenta también puede pedir ayuda.',
            ])->assertRedirect(route('dashboard'));
        }

        $this->assertDatabaseCount('job_requests', 2);
        $this->assertDatabaseCount('posts', 2);
    }

    public function test_a_client_can_publish_a_structured_job_request(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'job_request',
            'category_id' => $this->categoryId(),
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
            'category_id' => $this->categoryId(),
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
                'category_id' => $this->categoryId(),
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
            'category_id' => $this->categoryId(),
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

    public function test_a_new_publication_requires_a_primary_category(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)
            ->from(route('dashboard'))
            ->post(route('posts.store'), [
                'submission_token' => (string) Str::uuid(),
                'type' => 'job_request',
                'title' => 'Necesito apoyo con una reparación',
                'urgency' => 'normal',
                'body' => 'Descripción suficientemente clara de la solicitud local.',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('posts', 0);
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
            ->get(route('dashboard', ['module' => 'products']))
            ->assertOk()
            ->assertSee('Promoción especial disponible');
    }

    public function test_pending_provider_cannot_publish_commercial_offer(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        Vendor::create(['user_id' => $provider->id, 'display_name' => $provider->name, 'slug' => 'pendiente-'.$provider->id, 'status' => 'pending']);
        $response = $this->actingAs($provider)->post(route('posts.store'), [
            'submission_token' => (string) Str::uuid(),
            'type' => 'service',
            'category_id' => $this->categoryId(),
            'body' => 'Servicio profesional todavía pendiente de aprobación.',
            'title' => 'Servicio pendiente',
            'price_type' => 'quote',
        ]);

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('type');
        $this->assertDatabaseCount('posts', 0);
    }

    private function approvedProvider(): User
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        Vendor::create(['user_id' => $provider->id, 'display_name' => $provider->name, 'slug' => 'aprobado-'.$provider->id, 'status' => 'active']);

        return $provider;
    }

    private function categoryId(): int
    {
        return Category::query()->firstOrCreate(
            ['slug' => 'servicios-locales'],
            ['name' => 'Servicios locales', 'is_active' => true],
        )->id;
    }
}
