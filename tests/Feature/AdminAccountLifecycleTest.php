<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_suspend_and_reactivate_a_commercial_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $client = User::factory()->create(['password' => 'Seguro123']);

        $this->actingAs($admin)->patch(route('admin.users.suspend', $client), [
            'reason' => 'Actividad irregular que requiere revisión administrativa.',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $client->refresh();
        $this->assertSame('suspended', $client->account_status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'account.suspended', 'subject_id' => $client->id]);

        $this->actingAs($client)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post(route('login.store'), ['email' => $client->email, 'password' => 'Seguro123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->patch(route('admin.users.reactivate', $client))->assertRedirect();
        $this->assertSame('active', $client->fresh()->account_status);
    }

    public function test_only_superadmin_can_deactivate_and_restore_a_deactivated_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $client = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.deactivate', $client), [
            'reason' => 'Baja administrativa solicitada y documentada correctamente.',
        ])->assertForbidden();

        $this->actingAs($superadmin)->patch(route('admin.users.deactivate', $client), [
            'reason' => 'Baja administrativa solicitada y documentada correctamente.',
        ])->assertRedirect();
        $this->assertSame('deactivated', $client->fresh()->account_status);

        $this->actingAs($admin)->patch(route('admin.users.reactivate', $client))->assertForbidden();
        $this->actingAs($superadmin)->patch(route('admin.users.reactivate', $client))->assertRedirect();
        $this->assertSame('active', $client->fresh()->account_status);
    }

    public function test_account_directory_moves_lifecycle_controls_to_the_account_dashboard(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $client = User::factory()->create(['name' => 'Cliente moderable']);

        $this->actingAs($superadmin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Administrar')
            ->assertSee(route('admin.users.show', $client), false)
            ->assertDontSee(route('admin.users.suspend', $client), false)
            ->assertDontSee('Aplicar filtros');

        $this->actingAs($superadmin)->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertSee('Control de la cuenta')
            ->assertSee(route('admin.users.suspend', $client), false)
            ->assertSee('Dar de baja');

        $this->actingAs($superadmin)->patch(route('admin.users.suspend', $superadmin), [
            'reason' => 'Este intento debe ser rechazado por seguridad.',
        ])->assertStatus(422);
    }

    public function test_suspended_account_dashboard_offers_reactivation_instead_of_suspension(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $client = User::factory()->create([
            'account_status' => 'suspended',
            'account_status_reason' => 'Revisión administrativa todavía pendiente.',
        ]);

        $this->actingAs($superadmin)->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertSee('Reactivar cuenta')
            ->assertSee(route('admin.users.reactivate', $client), false)
            ->assertDontSee('Suspender cuenta');
    }

    public function test_account_and_commercial_suspensions_are_presented_as_separate_states(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $client = User::factory()->create(['account_status' => 'active']);
        $vendor = Vendor::create([
            'user_id' => $client->id,
            'display_name' => 'Comercio suspendido',
            'slug' => 'comercio-suspendido',
            'status' => 'suspended',
        ]);

        $this->actingAs($admin)->get(route('admin.users.show', $client))
            ->assertOk()
            ->assertSee('Actividad comercial suspendida')
            ->assertSee('Acceso a Plaza Local')
            ->assertSee('Activa')
            ->assertSee(route('admin.vendors.show', $vendor), false);
    }
}
