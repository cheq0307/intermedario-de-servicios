<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $vendor = Vendor::create(['user_id' => $provider->id, 'display_name' => 'Negocio pendiente', 'slug' => 'negocio-pendiente', 'status' => 'pending']);
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

    public function test_regular_user_cannot_access_administration(): void
    {
        $client = User::factory()->create();
        $this->actingAs($client)->get(route('admin.index'))->assertForbidden();
    }
}
