<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_account_directory_exposes_lifecycle_controls_without_targeting_authorities(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $client = User::factory()->create(['name' => 'Cliente moderable']);

        $this->actingAs($superadmin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Estado de cuenta')
            ->assertSee(route('admin.users.suspend', $client), false)
            ->assertSee(route('admin.users.deactivate', $client), false)
            ->assertSee('Dar de baja');

        $this->actingAs($superadmin)->patch(route('admin.users.suspend', $superadmin), [
            'reason' => 'Este intento debe ser rechazado por seguridad.',
        ])->assertStatus(422);
    }
}
