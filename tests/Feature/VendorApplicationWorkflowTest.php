<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Community;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_activating_provider_creates_a_draft_not_an_admin_request(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($client)->post(route('capabilities.activate', 'provider'))->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('vendors', ['user_id' => $client->id, 'status' => 'draft', 'submitted_at' => null]);
        $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['pending_vendors'] === 0);
    }

    public function test_provider_must_verify_email_and_complete_profile_before_submitting(): void
    {
        $provider = User::factory()->unverified()->create(['account_type' => 'provider']);
        Vendor::create(['user_id' => $provider->id, 'display_name' => $provider->name, 'slug' => 'borrador-'.$provider->id, 'status' => 'draft']);

        $this->actingAs($provider)->post(route('provider-applications.submit'))->assertRedirect(route('verification.notice'));
        $this->assertSame('draft', $provider->vendor->fresh()->status);
    }

    public function test_complete_provider_enters_queue_only_after_submitting(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id, 'display_name' => 'Servicios Luna', 'slug' => 'servicios-luna',
            'description' => 'Servicios profesionales para la comunidad.', 'specialty' => 'Plomería', 'service_area' => 'Centro',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'], 'status' => 'draft',
        ]);
        $vendor->categories()->attach(Category::query()->value('id'));
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($provider)->post(route('provider-applications.submit'))->assertRedirect(route('profile.edit'));

        $this->assertSame('pending', $vendor->fresh()->status);
        $this->assertNotNull($vendor->fresh()->submitted_at);
        $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['pending_vendors'] === 1)
            ->assertSee('Servicios Luna')
            ->assertSee('data-notification-category="administrative"', false);
    }

    public function test_resubmitting_provider_application_notifies_admin_and_points_to_the_exact_record(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id, 'display_name' => 'Servicios reenviados', 'slug' => 'servicios-reenviados',
            'description' => 'Servicios profesionales para la comunidad.', 'specialty' => 'Reparaciones', 'service_area' => 'Centro',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'rejected', 'submitted_at' => now()->subDay(), 'rejection_reason' => 'Completa la información.',
        ]);
        $vendor->categories()->attach(Category::query()->value('id'));
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));

        $this->actingAs($provider)
            ->post(route('provider-applications.submit'))
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $this->assertSame('pending', $vendor->fresh()->status);
        $this->assertNull($vendor->fresh()->rejection_reason);
        $this->assertDatabaseHas('audit_logs', ['action' => 'vendor.submitted', 'subject_id' => $vendor->id]);
        $notification = $superadmin->notifications()->sole();
        $this->assertSame('admin.vendors.show', $notification->data['route_name']);
        $this->assertSame(['vendor' => $vendor->id], $notification->data['route_parameters']);
        $this->assertSame('vendor_submitted', $notification->data['kind']);
        $this->actingAs($superadmin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Notificaciones')
            ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['pending_vendors'] === 1);
    }

    public function test_editing_a_submitted_profile_returns_it_to_draft(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create(['user_id' => $provider->id, 'display_name' => 'Servicios Luna', 'slug' => 'servicios-luna-edicion', 'status' => 'pending', 'submitted_at' => now()]);

        $this->actingAs($provider)->put(route('profile.update'), [
            'name' => $provider->name, 'community_id' => Community::query()->value('id'), 'display_name' => 'Servicios Luna Actualizados', 'specialty' => 'Plomería',
            'offers_services' => 1, 'offered_categories' => [Category::query()->value('id')],
            'service_area' => 'Centro', 'description' => 'Descripción actualizada y completa.', 'availability_status' => 'available',
            'business_days' => ['monday'], 'business_opens_at' => '09:00', 'business_closes_at' => '18:00',
        ])->assertRedirect();

        $this->assertSame('draft', $vendor->fresh()->status);
        $this->assertNull($vendor->fresh()->submitted_at);
    }
}
