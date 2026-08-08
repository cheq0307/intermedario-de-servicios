<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketplaceCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_activate_provider_capability_without_creating_another_account(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)
            ->post(route('capabilities.activate', 'provider'))
            ->assertRedirect(route('profile.edit'));

        $client->refresh();
        $this->assertTrue($client->canActAsClient());
        $this->assertTrue($client->canActAsProvider());
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('vendors', [
            'user_id' => $client->id,
            'status' => 'pending',
        ]);
    }

    public function test_account_with_both_capabilities_can_switch_context(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);
        $user->assignRole(Role::findOrCreate('provider'));

        $this->actingAs($user)
            ->post(route('capabilities.switch', 'provider'))
            ->assertRedirect();

        $this->assertSame('provider', session('marketplace_mode'));

        $this->post(route('capabilities.switch', 'client'))->assertRedirect();
        $this->assertSame('client', session('marketplace_mode'));
    }

    public function test_user_cannot_switch_to_a_capability_they_do_not_have(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)
            ->post(route('capabilities.switch', 'provider'))
            ->assertForbidden();
    }

    public function test_unverified_user_cannot_activate_another_capability(): void
    {
        $client = User::factory()->unverified()->create(['account_type' => 'client']);

        $this->actingAs($client)
            ->post(route('capabilities.activate', 'provider'))
            ->assertRedirect(route('verification.notice'));

        $this->assertFalse($client->fresh()->canActAsProvider());
        $this->assertDatabaseMissing('vendors', ['user_id' => $client->id]);
    }

    public function test_superadmin_can_leave_an_administrator_without_commercial_capabilities(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $staff = User::factory()->create(['account_type' => 'client']);
        $staff->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($superadmin)
            ->delete(route('admin.users.capabilities.revoke', [$staff, 'client']))
            ->assertRedirect();

        $staff->refresh();
        $this->assertTrue($staff->hasRole('admin'));
        $this->assertFalse($staff->canActAsClient());
        $this->assertFalse($staff->canActAsProvider());
    }

    public function test_superadmin_cannot_leave_a_regular_user_without_any_capability(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($superadmin)
            ->delete(route('admin.users.capabilities.revoke', [$client, 'client']))
            ->assertStatus(422);

        $this->assertTrue($client->fresh()->canActAsClient());
    }

    public function test_revoking_provider_suspends_profile_but_preserves_it(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $provider = User::factory()->create(['account_type' => 'provider']);
        $provider->assignRole(Role::findOrCreate('client'));
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Negocio conservado',
            'slug' => 'negocio-conservado',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->delete(route('admin.users.capabilities.revoke', [$provider, 'provider']))
            ->assertRedirect();

        $this->assertFalse($provider->fresh()->canActAsProvider());
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'status' => 'suspended',
        ]);
    }

    public function test_dual_account_dashboard_switches_the_publication_form(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);
        $user->assignRole(Role::findOrCreate('provider'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Cambia de contexto sin cerrar sesión.')
            ->assertSee('¿Qué necesitas resolver hoy?');
        $this->assertSame('client', session('marketplace_mode'));

        $this->post(route('capabilities.switch', 'provider'))->assertRedirect();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Nombre del producto o servicio');
    }

    public function test_staff_only_dashboard_does_not_offer_commercial_publication(): void
    {
        $staff = User::factory()->create(['account_type' => 'client']);
        $staff->assignRole(Role::findOrCreate('admin'));
        $staff->removeRole('client');

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Esta cuenta es personal administrativo')
            ->assertSee('Administra la operación local.')
            ->assertDontSee('Crea una publicación');
    }
}
