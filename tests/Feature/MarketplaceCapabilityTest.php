<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarketplaceCapabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_start_commercial_profile_without_premature_permission(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client, 'web')
            ->post(route('capabilities.activate', 'provider'))
            ->assertRedirect(route('profile.edit'));

        $client->refresh();
        $this->assertTrue($client->canActAsClient());
        $this->assertFalse($client->canActAsProvider());
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

        $this->actingAs($user, 'web')
            ->post(route('capabilities.switch', 'provider'))
            ->assertRedirect();

        $this->assertSame('provider', session('marketplace_mode'));

        $this->post(route('capabilities.switch', 'client'))->assertRedirect();
        $this->assertSame('client', session('marketplace_mode'));
    }

    public function test_user_cannot_switch_to_a_capability_they_do_not_have(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client, 'web')
            ->post(route('capabilities.switch', 'provider'))
            ->assertForbidden();
    }

    public function test_unverified_user_cannot_activate_another_capability(): void
    {
        $client = User::factory()->unverified()->create(['account_type' => 'client']);

        $this->actingAs($client, 'web')
            ->post(route('capabilities.activate', 'provider'))
            ->assertRedirect(route('verification.notice'));

        $this->assertFalse($client->fresh()->canActAsProvider());
        $this->assertDatabaseMissing('vendors', ['user_id' => $client->id]);
    }

    public function test_superadmin_cannot_activate_commercial_capabilities(): void
    {
        $superadmin = AdminUser::factory()->superadmin()->create();

        $this->actingAs($superadmin, 'admin')->post(route('capabilities.activate', 'provider'))->assertRedirect(route('login'));

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

        $this->actingAs($superadmin, 'web')
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->post(route('capabilities.switch', 'provider'))->assertForbidden();
        $this->get(route('explore'))->assertRedirect(route('admin.index'));
    }

    public function test_unified_dashboard_opens_the_requested_composer_only(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);
        $user->assignRole(Role::findOrCreate('provider'));

        $this->actingAs($user, 'web')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Publicar')
            ->assertSee('Más')
            ->assertDontSee('data-publication-form', false);

        $this->get(route('dashboard', ['publicar' => 'request']))
            ->assertOk()->assertSee('¿Qué necesitas?');
        $this->get(route('dashboard', ['publicar' => 'offer']))
            ->assertOk()
            ->assertSee('Nombre del producto o servicio');
    }

    public function test_dashboard_uses_the_mobile_bottom_navigation(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($user, 'web')
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pedidos')
            ->assertSee('Más')
            ->assertSee('aria-label="Navegación principal"', false)
            ->assertDontSee('Abrir menú');

    }

    public function test_administrator_with_legacy_commercial_roles_is_kept_inside_administration(): void
    {
        $staff = User::factory()->create(['account_type' => 'client']);
        $staff->assignRole([
            Role::findOrCreate('client'),
            Role::findOrCreate('provider'),
            Role::findOrCreate('admin'),
        ]);

        $this->actingAs($staff, 'web')
            ->get(route('dashboard'))
            ->assertForbidden();

        $this->assertFalse($staff->fresh()->canUseMarketplace());
        $this->assertFalse($staff->fresh()->canActAsClient());
        $this->assertFalse($staff->fresh()->canActAsProvider());
        $this->post(route('capabilities.switch', 'provider'))->assertForbidden();
        $this->get(route('explore'))->assertRedirect(route('admin.index'));
    }
}
