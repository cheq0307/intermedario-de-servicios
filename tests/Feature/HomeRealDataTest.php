<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HomeRealDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_shows_authentication_actions(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('aria-label="Iniciar sesión"', false)
            ->assertSee('Abrir menú')
            ->assertSee('Crear cuenta')
            ->assertSee(route('login'), false)
            ->assertSee(route('register'), false);
    }

    public function test_authenticated_commercial_user_cannot_return_to_public_home(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_superadmin_cannot_return_to_public_home_and_reaches_administration(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->syncRoles([Role::findOrCreate('superadmin')]);

        $this->actingAs($superadmin)
            ->get(route('home'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.index'));
    }

    public function test_home_uses_approved_real_listings_and_search_is_public(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create(['user_id' => $provider->id, 'display_name' => 'Carpinteria Luna', 'slug' => 'carpinteria-luna', 'status' => 'active']);
        Listing::create(['vendor_id' => $vendor->id, 'type' => 'service', 'name' => 'Reparacion de muebles', 'slug' => 'reparacion-muebles', 'description' => 'Trabajo local.', 'price_type' => 'quote', 'is_active' => true]);

        $this->get(route('home'))->assertOk()->assertSee('Reparacion de muebles')->assertSee(route('explore'));
        $this->get(route('explore', ['q' => 'muebles']))->assertOk()->assertSee('Reparacion de muebles');
    }
}
