<?php

namespace Tests\Feature;

use App\Models\User;
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
            'status' => 'draft',
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

    public function test_superadmin_cannot_activate_commercial_capabilities(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->syncRoles([Role::findOrCreate('superadmin')]);

        $this->actingAs($superadmin)->post(route('capabilities.activate', 'provider'))->assertForbidden();

        $this->assertFalse($superadmin->fresh()->canActAsProvider());
    }

    public function test_legacy_superadmin_with_commercial_roles_is_kept_inside_administration(): void
    {
        $superadmin = User::factory()->create(['account_type' => 'client']);
        $superadmin->assignRole([
            Role::findOrCreate('client'),
            Role::findOrCreate('provider'),
            Role::findOrCreate('superadmin'),
        ]);

        $this->actingAs($superadmin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.index'));

        $this->post(route('capabilities.switch', 'provider'))->assertForbidden();
        $this->get(route('explore'))->assertRedirect(route('admin.index'));
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
            ->assertRedirect(route('admin.index'));
    }
}
