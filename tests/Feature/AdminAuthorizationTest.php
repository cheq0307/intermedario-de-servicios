<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_delegate_and_revoke_admin_without_granting_superadmin(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $target = User::factory()->create();

        $this->actingAs($superadmin)->post(route('admin.users.grant', $target))->assertRedirect();
        $this->assertTrue($target->fresh()->hasRole('admin'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'admin.granted', 'subject_id' => $target->id]);

        $this->actingAs($superadmin)->delete(route('admin.users.revoke', $target))->assertRedirect();
        $this->assertFalse($target->fresh()->hasRole('admin'));
    }

    public function test_delegated_admin_can_approve_and_suspend_vendor_but_cannot_delegate_admins(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Negocio pendiente',
            'slug' => 'negocio-pendiente',
            'description' => 'Servicios profesionales para la comunidad.',
            'specialty' => 'Reparaciones',
            'service_area' => 'Centro',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $target = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('Negocio pendiente');
        $this->actingAs($admin)->patch(route('admin.vendors.approve', $vendor))->assertRedirect();
        $this->assertSame('active', $vendor->fresh()->status);
        $this->assertNotNull($vendor->fresh()->verified_at);
        $this->actingAs($admin)->post(route('admin.users.grant', $target))->assertForbidden();

        $this->actingAs($admin)->patch(route('admin.vendors.suspend', $vendor), ['reason' => 'Documentación comercial inconsistente.'])->assertRedirect();
        $this->assertSame('suspended', $vendor->fresh()->status);
        $this->assertSame(2, AuditLog::where('user_id', $admin->id)->count());
    }

    public function test_incomplete_or_unverified_provider_cannot_be_approved(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $provider = User::factory()->unverified()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Perfil incompleto',
            'slug' => 'perfil-incompleto',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.vendors.approve', $vendor))
            ->assertStatus(422);

        $this->assertSame('pending', $vendor->fresh()->status);
    }

    public function test_approval_notifies_provider_and_admin_dashboard_shows_pending_queue(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Servicios listos',
            'slug' => 'servicios-listos',
            'description' => 'Trabajo profesional.',
            'specialty' => 'Electricidad',
            'service_area' => 'Centro',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Proveedores pendientes')
            ->assertSee('Servicios listos')
            ->assertSee('Aprobar proveedor');

        $this->patch(route('admin.vendors.approve', $vendor))->assertRedirect();

        Notification::assertSentTo($provider, MarketplaceActivity::class);
    }

    public function test_commercial_administrator_sees_responsive_administration_shortcut(): void
    {
        $admin = User::factory()->create(['account_type' => 'client']);
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Abrir administración')
            ->assertSee(route('admin.index'), false);
    }

    public function test_administrator_cannot_approve_own_vendor_profile(): void
    {
        $admin = User::factory()->create(['account_type' => 'provider']);
        $admin->assignRole(Role::findOrCreate('admin'));
        $vendor = Vendor::create([
            'user_id' => $admin->id,
            'display_name' => 'Negocio del admin',
            'slug' => 'negocio-admin',
            'description' => 'Perfil completo.',
            'specialty' => 'Oficio',
            'service_area' => 'Centro',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.vendors.approve', $vendor))
            ->assertForbidden();

        $this->assertSame('pending', $vendor->fresh()->status);
    }

    public function test_admin_workspace_excludes_current_operator_from_third_person_management(): void
    {
        $superadmin = User::factory()->create(['name' => 'Cuenta propietaria']);
        $superadmin->syncRoles([Role::findOrCreate('superadmin')]);
        User::factory()->create(['name' => 'Otra persona']);

        $this->actingAs($superadmin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Otra persona')
            ->assertDontSee('Cuenta propietaria')
            ->assertSee('Explorar plaza')
            ->assertDontSee('Capacidades comerciales');
    }

    public function test_administrator_can_reject_a_submitted_provider_application_with_a_reason(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Solicitud revisable',
            'slug' => 'solicitud-revisable',
            'description' => 'Servicios profesionales.',
            'specialty' => 'Electricidad',
            'service_area' => 'Centro',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('admin.vendors.reject', $vendor), ['reason' => 'Necesitamos una descripción más precisa.'])->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'rejected', 'rejection_reason' => 'Necesitamos una descripción más precisa.']);
        Notification::assertSentTo($provider, MarketplaceActivity::class);
    }

    public function test_regular_user_cannot_access_administration(): void
    {
        $client = User::factory()->create();
        $this->actingAs($client)->get(route('admin.index'))->assertForbidden();
    }
}
