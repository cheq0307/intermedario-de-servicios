<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Community;
use App\Models\Post;
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
        $vendor->categories()->attach(Category::query()->value('id'));
        $target = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('1 solicitudes pendientes')->assertDontSee('Negocio pendiente');
        $this->actingAs($admin)->patch(route('admin.vendors.approve', $vendor))->assertRedirect();
        $this->assertSame('active', $vendor->fresh()->status);
        $this->assertNull($vendor->fresh()->verified_at);
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('Suspender proveedor')->assertSee('Moderación de proveedores');
        $this->actingAs($admin)->post(route('admin.users.grant', $target))->assertForbidden();

        $this->actingAs($admin)->patch(route('admin.vendors.suspend', $vendor), ['reason' => 'Documentación comercial inconsistente.'])->assertRedirect();
        $this->assertSame('suspended', $vendor->fresh()->status);
        $this->assertSame('Documentación comercial inconsistente.', $vendor->fresh()->suspension_reason);
        $this->assertNotNull($vendor->fresh()->suspended_at);
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
        $vendor->categories()->attach(Category::query()->value('id'));

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('1 solicitudes pendientes')
            ->assertDontSee('Aprobar proveedor');

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
        User::factory()->create(['name' => 'Otra persona', 'email' => 'otra@example.test']);

        $this->actingAs($superadmin)
            ->get(route('admin.index', ['admin_q' => 'Otra']))
            ->assertOk()
            ->assertSee('Otra persona')
            ->assertDontSee('Cuenta propietaria')
            ->assertSee('Cerrar sesi')
            ->assertSee('Control global de comunidades')
            ->assertDontSee('Explorar plaza')
            ->assertDontSee('Ir a mi cuenta comercial')
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

    public function test_admin_can_create_a_community_and_audit_is_human_readable(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($admin)
            ->post(route('admin.communities.store'), [
                'name' => 'San Miguel',
                'municipality' => 'Municipio Ejemplo',
                'state' => 'Puebla',
                'latitude' => 19.4326077,
                'longitude' => -99.1332080,
                'default_radius_km' => 12,
            ])
            ->assertRedirect();

        $community = Community::where('name', 'San Miguel')->firstOrFail();
        $this->assertSame('19.4326077', $community->latitude);
        $this->assertSame('-99.1332080', $community->longitude);
        $this->assertSame('12.00', $community->default_radius_km);
        $this->assertDatabaseHas('audit_logs', ['action' => 'community.created', 'subject_id' => $community->id]);
        $this->patch(route('admin.communities.update', $community), [
            'latitude' => 19.5000000,
            'longitude' => -99.2000000,
            'default_radius_km' => 20,
        ])->assertRedirect();
        $this->assertSame('19.5000000', $community->fresh()->latitude);
        $this->assertSame('20.00', $community->fresh()->default_radius_km);
        $this->assertDatabaseHas('audit_logs', ['action' => 'community.updated', 'subject_id' => $community->id]);

        $this->get(route('admin.index'))
            ->assertOk()
            ->assertSee('¿Qué puede hacer cada administrador?')
            ->assertSee('Comunidad agregada')
            ->assertDontSee('community.created');
    }

    public function test_admin_candidates_only_appear_after_a_search(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->syncRoles([Role::findOrCreate('superadmin')]);
        $candidate = User::factory()->create(['name' => 'Candidata Delegada', 'email' => 'delegada@example.test']);

        $this->actingAs($superadmin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertDontSee($candidate->email)
            ->assertSee('Buscar usuario');

        $this->get(route('admin.index', ['admin_q' => 'delegada@example.test']))
            ->assertOk()
            ->assertSee($candidate->email)
            ->assertSee('Hacer administrador');
    }

    public function test_admin_can_edit_suspend_and_reactivate_a_community_but_cannot_delete_it(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $community = Community::create([
            'name' => 'Pueblo operativo',
            'municipality' => 'Municipio original',
            'state' => 'Puebla',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)->patch(route('admin.communities.update', $community), [
            'name' => 'Pueblo actualizado',
            'municipality' => 'Municipio actualizado',
            'state' => 'Puebla',
            'postal_code' => '74140',
            'latitude' => 19.233,
            'longitude' => -98.5,
            'default_radius_km' => 12,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('communities', ['id' => $community->id, 'name' => 'Pueblo actualizado', 'postal_code' => '74140']);
        $this->patch(route('admin.communities.toggle', $community))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($community->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'community.suspended', 'subject_id' => $community->id]);

        $this->patch(route('admin.communities.toggle', $community))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertTrue($community->fresh()->is_active);
        $this->delete(route('admin.communities.destroy', $community))->assertForbidden();
    }

    public function test_superadmin_can_delete_only_an_empty_suspended_community(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $community = Community::create([
            'name' => 'Comunidad temporal',
            'municipality' => 'Municipio temporal',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->delete(route('admin.communities.destroy', $community))
            ->assertRedirect()
            ->assertSessionHasErrors('community');
        $this->assertDatabaseHas('communities', ['id' => $community->id]);

        $this->patch(route('admin.communities.toggle', $community))->assertRedirect()->assertSessionHasNoErrors();
        $this->delete(route('admin.communities.destroy', $community))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('communities', ['id' => $community->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'community.deleted', 'subject_id' => $community->id]);
    }

    public function test_used_community_is_preserved_as_suspended_history(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin'));
        $community = Community::create([
            'name' => 'Comunidad con historial',
            'municipality' => 'Municipio histórico',
            'default_radius_km' => 8,
            'is_active' => false,
        ]);
        User::factory()->create(['community_id' => $community->id]);

        $this->actingAs($superadmin)
            ->delete(route('admin.communities.destroy', $community))
            ->assertRedirect()
            ->assertSessionHasErrors('community');
        $this->assertDatabaseHas('communities', ['id' => $community->id, 'is_active' => false]);
    }

    public function test_last_active_community_cannot_be_suspended(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $community = Community::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.communities.toggle', $community))
            ->assertRedirect()
            ->assertSessionHasErrors('community');
        $this->assertTrue($community->fresh()->is_active);
    }

    public function test_regular_user_cannot_access_administration(): void
    {
        $client = User::factory()->create();
        $this->actingAs($client)->get(route('admin.index'))->assertForbidden();
    }

    public function test_admin_can_remove_a_post_with_reason_audit_and_author_notification(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $author = User::factory()->create();
        $author->assignRole(Role::findOrCreate('client'));
        $post = Post::create([
            'user_id' => $author->id,
            'type' => 'business_update',
            'body' => 'Contenido que necesita moderación administrativa.',
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.posts.remove', $post), ['reason' => 'Incumple las reglas de publicación de la comunidad.'])
            ->assertRedirect();

        $post->refresh();
        $this->assertNotNull($post->removed_at);
        $this->assertSame($admin->id, $post->removed_by_user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'post.removed', 'subject_id' => $post->id]);
        Notification::assertSentTo($author, MarketplaceActivity::class);

        $this->actingAs($author)->get(route('dashboard'))->assertDontSee($post->body);
        $this->get(route('profile.show', $author))->assertDontSee($post->body);
        $this->actingAs($admin)->get(route('admin.posts.index', ['status' => 'removed']))
            ->assertOk()
            ->assertSee('Incumple las reglas de publicación de la comunidad.');
    }

    public function test_regular_user_cannot_remove_a_post(): void
    {
        $author = User::factory()->create();
        $author->assignRole(Role::findOrCreate('client'));
        $post = Post::create([
            'user_id' => $author->id,
            'type' => 'business_update',
            'body' => 'Publicación válida y visible para la comunidad.',
            'published_at' => now(),
        ]);

        $this->actingAs($author)
            ->patch(route('admin.posts.remove', $post), ['reason' => 'Intento no autorizado de retirar contenido.'])
            ->assertForbidden();

        $this->assertNull($post->fresh()->removed_at);
    }
}
