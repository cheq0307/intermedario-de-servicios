<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeRealDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_uses_approved_real_listings_and_search_is_public(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create(['user_id' => $provider->id, 'display_name' => 'Carpinteria Luna', 'slug' => 'carpinteria-luna', 'status' => 'active']);
        Listing::create(['vendor_id' => $vendor->id, 'type' => 'service', 'name' => 'Reparacion de muebles', 'slug' => 'reparacion-muebles', 'description' => 'Trabajo local.', 'price_type' => 'quote', 'is_active' => true]);

        $this->get(route('home'))->assertOk()->assertSee('Reparacion de muebles')->assertSee(route('explore'));
        $this->get(route('explore', ['q' => 'muebles']))->assertOk()->assertSee('Reparacion de muebles');
    }
}
