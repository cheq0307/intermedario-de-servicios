<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Listing;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedPersonalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unified_user_feed_shows_requests_and_offers(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $otherClient = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);

        $this->createPost($provider, 'service', 'Servicio profesional visible para clientes.');
        $this->createPost($otherClient, 'job_request', 'Solicitud de otro cliente oculta por defecto.');
        $this->createPost($client, 'job_request', 'Mi propia solicitud permanece visible.');

        $this->actingAs($client)
            ->get(route('dashboard', ['feed' => 'for_you']))
            ->assertOk()
            ->assertSee('Servicio profesional visible para clientes.')
            ->assertSee('Mi propia solicitud permanece visible.')
            ->assertSee('Solicitud de otro cliente oculta por defecto.')
            ->assertSee('Mostramos solicitudes y ofertas de toda la comunidad');
    }

    public function test_commercial_user_still_sees_other_offers_and_requests(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $otherProvider = User::factory()->create(['account_type' => 'provider']);
        $client = User::factory()->create(['account_type' => 'client']);

        $this->createPost($client, 'job_request', 'Cliente busca una reparación de plomería.');
        $this->createPost($otherProvider, 'service', 'Oferta de otro proveedor oculta por defecto.');
        $this->createPost($provider, 'promotion', 'Mi promoción permanece visible para administrarla.');

        $this->actingAs($provider)
            ->get(route('dashboard', ['feed' => 'for_you']))
            ->assertOk()
            ->assertSee('Cliente busca una reparación de plomería.')
            ->assertSee('Mi promoción permanece visible para administrarla.')
            ->assertSee('Oferta de otro proveedor oculta por defecto.')
            ->assertSee('Mostramos solicitudes y ofertas de toda la comunidad');
    }

    public function test_default_feed_prioritizes_personalized_content_without_hiding_public_types(): void
    {
        $viewer = User::factory()->create(['account_type' => 'client']);
        $client = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);

        $this->createPost($provider, 'service', 'Oferta visible en el feed general.');
        $this->createPost($client, 'job_request', 'Solicitud visible en el feed general.');

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Para ti')
            ->assertSee('Oferta visible en el feed general.')
            ->assertSee('Solicitud visible en el feed general.');
    }

    public function test_feed_tabs_allow_explicit_discovery_without_changing_capabilities(): void
    {
        $viewer = User::factory()->create(['account_type' => 'client']);
        $client = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);

        $this->createPost($provider, 'product', 'Producto dentro de la pestaña de ofertas.');
        $this->createPost($provider, 'portfolio', 'Trabajo terminado dentro de comunidad.');
        $this->createPost($client, 'job_request', 'Solicitud dentro de su pestaña especializada.');

        $this->actingAs($viewer)->get(route('dashboard', ['feed' => 'requests']))
            ->assertOk()
            ->assertSee('Solicitud dentro de su pestaña especializada.')
            ->assertDontSee('Producto dentro de la pestaña de ofertas.');

        $this->get(route('dashboard', ['feed' => 'community']))
            ->assertOk()
            ->assertSee('Trabajo terminado dentro de comunidad.')
            ->assertDontSee('Solicitud dentro de su pestaña especializada.');

        $this->get(route('dashboard', ['feed' => 'all']))
            ->assertOk()
            ->assertSee('Producto dentro de la pestaña de ofertas.')
            ->assertSee('Trabajo terminado dentro de comunidad.')
            ->assertSee('Solicitud dentro de su pestaña especializada.');
    }

    public function test_suspended_provider_offers_are_hidden_but_client_requests_remain_visible(): void
    {
        $viewer = User::factory()->create(['account_type' => 'client']);
        $dualUser = User::factory()->create(['account_type' => 'provider']);
        $dualUser->assignRole('client');
        $dualUser->vendor()->create([
            'display_name' => 'Proveedor suspendido',
            'slug' => 'proveedor-suspendido-'.$dualUser->id,
            'status' => 'suspended',
            'suspension_reason' => 'Incumplimiento reiterado de las reglas.',
            'suspended_at' => now(),
        ]);

        $this->createPost($dualUser, 'service', 'Oferta comercial que debe ocultarse por suspensión.');
        $this->createPost($dualUser, 'job_request', 'Solicitud como cliente que debe permanecer visible.');

        $this->actingAs($viewer)
            ->get(route('dashboard', ['feed' => 'all']))
            ->assertOk()
            ->assertDontSee('Oferta comercial que debe ocultarse por suspensión.')
            ->assertSee('Solicitud como cliente que debe permanecer visible.');
    }

    public function test_dashboard_showcase_groups_real_approved_offers_by_category(): void
    {
        $viewer = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);
        $category = Category::query()->firstOrCreate(['slug' => 'comida-bebidas'], ['name' => 'Comida y bebidas', 'is_active' => true]);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Cocina La Esquina',
            'slug' => 'cocina-la-esquina',
            'status' => 'active',
        ]);
        $listing = Listing::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'type' => 'product',
            'name' => 'Tacos dorados familiares',
            'slug' => 'tacos-dorados-familiares',
            'description' => 'Comida preparada en la comunidad.',
            'price_type' => 'fixed',
            'price_amount' => 18000,
            'stock' => 20,
            'is_active' => true,
        ]);
        $post = Post::create([
            'user_id' => $provider->id,
            'vendor_id' => $vendor->id,
            'listing_id' => $listing->id,
            'type' => 'product',
            'body' => $listing->description,
            'published_at' => now(),
        ]);
        PostMedia::create([
            'post_id' => $post->id,
            'type' => 'image',
            'path' => 'posts/tacos.webp',
            'position' => 0,
            'alt_text' => 'Orden de tacos dorados',
        ]);

        $this->actingAs($viewer)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Escaparate local')
            ->assertSee('Comida y bebidas')
            ->assertSee('Tacos dorados familiares')
            ->assertSee('data-market-carousel', false)
            ->assertSee('storage/posts/tacos.webp', false);
    }

    private function createPost(User $user, string $type, string $body): Post
    {

        return Post::create([
            'user_id' => $user->id,
            'type' => $type,
            'body' => $body,
            'published_at' => now(),
        ]);
    }
}
