<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\JobRequest;
use App\Models\Listing;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExploreTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_active_listings_providers_and_requests(): void
    {
        [$provider, $vendor] = $this->provider('Plomería Central', 'Plomería');
        Listing::create(['vendor_id' => $vendor->id, 'type' => 'service', 'name' => 'Reparación de tuberías', 'slug' => 'reparacion', 'description' => 'Arreglamos fugas.', 'price_type' => 'fixed', 'price_amount' => 50000, 'stock' => null, 'is_active' => true]);
        $client = User::factory()->create(['account_type' => 'client', 'city' => 'Mi Pueblo']);
        JobRequest::create(['public_id' => (string) Str::uuid(), 'client_id' => $client->id, 'title' => 'Necesito plomero', 'description' => 'Hay una fuga importante en casa.', 'status' => 'published', 'urgency' => 'urgent', 'published_at' => now()]);

        $this->actingAs($client)->get(route('explore', ['q' => 'Plomería']))->assertOk()->assertSee('Plomería Central')->assertSee('Reparación de tuberías');
        $this->actingAs($provider)->get(route('explore', ['q' => 'plomero', 'type' => 'job_request']))->assertOk()->assertSee('Necesito plomero');
    }

    public function test_suspended_vendor_and_inactive_listing_are_hidden(): void
    {
        [, $vendor] = $this->provider('Comercio suspendido', 'Ventas', 'suspended');
        Listing::create(['vendor_id' => $vendor->id, 'type' => 'product', 'name' => 'Producto oculto', 'slug' => 'oculto', 'price_type' => 'fixed', 'price_amount' => 10000, 'stock' => 2, 'is_active' => true]);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get(route('explore'))->assertOk()->assertDontSee('Comercio suspendido')->assertDontSee('Producto oculto');
    }

    public function test_user_can_limit_commercial_search_to_an_administered_community(): void
    {
        $mainCommunity = Community::query()->firstOrFail();
        $otherCommunity = Community::create(['name' => 'Pueblo vecino', 'municipality' => 'Municipio vecino', 'distance_km' => 35, 'is_active' => true]);
        [$mainProvider, $mainVendor] = $this->provider('Proveedor del centro', 'Plomería');
        [$otherProvider, $otherVendor] = $this->provider('Proveedor del pueblo vecino', 'Electricidad');
        $mainProvider->update(['community_id' => $mainCommunity->id]);
        $otherProvider->update(['community_id' => $otherCommunity->id]);
        Listing::create(['vendor_id' => $mainVendor->id, 'type' => 'service', 'name' => 'Servicio del centro', 'slug' => 'servicio-centro', 'price_type' => 'quote', 'is_active' => true]);
        Listing::create(['vendor_id' => $otherVendor->id, 'type' => 'service', 'name' => 'Servicio del pueblo vecino', 'slug' => 'servicio-vecino', 'price_type' => 'quote', 'is_active' => true]);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('explore', ['community_id' => $otherCommunity->id]))
            ->assertOk()
            ->assertSee('Servicio del pueblo vecino')
            ->assertDontSee('Servicio del centro')
            ->assertSee('El feed social muestra toda la actividad.');
    }

    public function test_price_filters_use_mxn_values(): void
    {
        [, $vendor] = $this->provider('Tienda Local', 'Productos');
        Listing::create(['vendor_id' => $vendor->id, 'type' => 'product', 'name' => 'Producto económico', 'slug' => 'economico', 'price_type' => 'fixed', 'price_amount' => 10000, 'stock' => 2, 'is_active' => true]);
        Listing::create(['vendor_id' => $vendor->id, 'type' => 'product', 'name' => 'Producto premium', 'slug' => 'premium', 'price_type' => 'fixed', 'price_amount' => 90000, 'stock' => 2, 'is_active' => true]);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)->get(route('explore', ['type' => 'product', 'max_price' => 200]))->assertOk()->assertSee('Producto económico')->assertDontSee('Producto premium');
    }

    private function provider(string $name, string $specialty, string $status = 'active'): array
    {
        $user = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create(['user_id' => $user->id, 'display_name' => $name, 'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)), 'specialty' => $specialty, 'status' => $status]);

        return [$user, $vendor];
    }
}
