<?php

namespace Tests\Feature;

use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
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

    public function test_superadmin_can_suspend_and_reactivate_team_access_without_promoting_clients(): void
    {
        $superadmin = AdminUser::factory()->superadmin()->create();
        $target = AdminUser::factory()->create();
        $this->actingAs($superadmin, 'admin')->patch(route('admin.team.toggle', $target))->assertRedirect();
        $this->assertFalse($target->fresh()->active);
        $this->patch(route('admin.team.toggle', $target))->assertRedirect();
        $this->assertTrue($target->fresh()->active);
        $this->assertFalse($target->fresh()->hasRole('superadmin'));
        $this->patch(route('admin.team.toggle', $superadmin))->assertForbidden();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_delegated_admin_can_approve_and_suspend_vendor_but_cannot_delegate_admins(): void
    {
        $admin = AdminUser::factory()->create();
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

        $this->actingAs($admin, 'admin')->get(route('admin.index'))->assertOk()->assertSee('1 cuentas por verificar')->assertDontSee('Negocio pendiente');
        $this->actingAs($admin, 'admin')->patch(route('admin.vendors.approve', $vendor))->assertRedirect();
        $this->assertSame('active', $vendor->fresh()->status);
        $this->assertNull($vendor->fresh()->verified_at);
        $this->actingAs($admin, 'admin')->get(route('admin.vendors.show', $vendor))->assertOk()->assertSee('Suspender actividad comercial')->assertSee('Moderación');
        $this->actingAs($admin, 'admin')->post(route('admin.team.invite'), ['name' => $target->name, 'email' => $target->email, 'phone' => $target->phone])->assertForbidden();

        $this->actingAs($admin, 'admin')->patch(route('admin.vendors.suspend', $vendor), ['reason' => 'Documentación comercial inconsistente.'])->assertRedirect();
        $this->assertSame('suspended', $vendor->fresh()->status);
        $this->assertSame('Documentación comercial inconsistente.', $vendor->fresh()->suspension_reason);
        $this->assertNotNull($vendor->fresh()->suspended_at);
        $this->assertSame(2, AuditLog::where('admin_user_id', $admin->id)->count());

        $suspensionNotification = $provider->notifications()->get()
            ->first(fn ($notification) => ($notification->data['kind'] ?? null) === 'vendor_suspended');
        $this->assertNotNull($suspensionNotification);
        $this->assertSame('support.create', $suspensionNotification->data['route_name']);
        $this->assertSame(['category' => 'provider_suspension'], $suspensionNotification->data['route_parameters']);

        $this->actingAs($provider, 'web')
            ->patch(route('notifications.open', $suspensionNotification))
            ->assertRedirect(route('support.create', ['category' => 'provider_suspension']));

        $this->actingAs($admin, 'admin')->get(route('admin.users.show', $provider))
            ->assertOk()
            ->assertSee('Reactivar actividad comercial')
            ->assertSee(route('admin.vendors.approve', $vendor), false);

        $this->actingAs($admin, 'admin')->patch(route('admin.vendors.approve', $vendor))->assertRedirect();
        $this->assertSame('active', $vendor->fresh()->status);
        $this->assertNotNull($provider->notifications()->get()->first(
            fn ($notification) => ($notification->data['kind'] ?? null) === 'vendor_reactivated'
        ));
    }

    public function test_provider_can_be_approved_without_fiscal_or_verification_documents(): void
    {
        $admin = AdminUser::factory()->create();
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Paletas de la comunidad',
            'slug' => 'paletas-comunidad',
            'description' => 'Venta local de paletas preparadas artesanalmente.',
            'specialty' => 'Paletas',
            'service_area' => 'Comunidad principal',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);
        $vendor->categories()->attach(Category::query()->value('id'));

        $this->actingAs($admin, 'admin')->patch(route('admin.vendors.approve', $vendor))->assertRedirect();

        $this->assertSame('active', $vendor->fresh()->status);
        $this->assertDatabaseCount('vendor_verification_documents', 0);
    }

    public function test_incomplete_or_unverified_provider_cannot_be_approved(): void
    {
        $admin = AdminUser::factory()->create();
        $provider = User::factory()->unverified()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Perfil incompleto',
            'slug' => 'perfil-incompleto',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.vendors.approve', $vendor))
            ->assertStatus(422);

        $this->assertSame('pending', $vendor->fresh()->status);
    }

    public function test_approval_notifies_provider_and_admin_dashboard_shows_pending_queue(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->create();
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

        $this->actingAs($admin, 'admin')
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('1 cuentas por verificar')
            ->assertDontSee('Habilitar actividad comercial');

        $this->patch(route('admin.vendors.approve', $vendor))->assertRedirect();

        Notification::assertSentTo($provider, MarketplaceActivity::class);
    }

    public function test_administrative_session_does_not_authenticate_in_marketplace(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin')->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.index'))->assertOk();
        $this->assertFalse($admin->canUseMarketplace());
    }

    public function test_administrator_cannot_approve_own_linked_vendor_profile(): void
    {
        $admin = AdminUser::factory()->create();
        $user = User::factory()->create(['email' => $admin->email, 'phone' => $admin->phone]);
        AccountIdentityLink::create(['admin_user_id' => $admin->id, 'user_id' => $user->id,
            'email' => $user->email, 'phone' => $user->phone, 'approved_at' => now()]);
        $vendor = Vendor::create(['user_id' => $user->id, 'display_name' => 'Propio', 'slug' => 'propio', 'status' => 'pending', 'submitted_at' => now()]);
        $this->actingAs($admin, 'admin')->patch(route('admin.vendors.approve', $vendor))->assertForbidden();
        $this->assertSame('pending', $vendor->fresh()->status);
    }

    public function test_admin_workspace_excludes_current_operator_from_third_person_management(): void
    {
        $superadmin = AdminUser::factory()->superadmin()->create(['name' => 'Cuenta propietaria']);
        User::factory()->create(['name' => 'Otra persona', 'email' => 'otra@example.test']);

        $this->actingAs($superadmin, 'admin')
            ->get(route('admin.index', ['admin_q' => 'Otra']))
            ->assertOk()
            ->assertDontSee('Otra persona')
            ->assertSee('Cuenta propietaria')
            ->assertSee('Cerrar sesi')
            ->assertSee('Cuenta exclusivamente administrativa')
            ->assertDontSee('Explorar plaza')
            ->assertDontSee('Ir a mi cuenta comercial')
            ->assertDontSee('Capacidades comerciales');
    }

    public function test_administrator_can_reject_a_submitted_provider_application_with_a_reason(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->create();
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

        $this->actingAs($admin, 'admin')->patch(route('admin.vendors.reject', $vendor), ['reason' => 'Necesitamos una descripción más precisa.'])->assertRedirect();

        $this->assertDatabaseHas('vendors', ['id' => $vendor->id, 'status' => 'rejected', 'rejection_reason' => 'Necesitamos una descripción más precisa.']);
        Notification::assertSentTo($provider, MarketplaceActivity::class);
    }

    public function test_admin_can_create_a_community_and_audit_is_human_readable(): void
    {
        $admin = AdminUser::factory()->create();

        $this->actingAs($admin, 'admin')
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
            ->assertSee('Información y alcance de mi cargo')
            ->assertSee('Comunidad agregada')
            ->assertDontSee('community.created');
    }

    public function test_admin_dashboard_uses_independent_paginators_for_growing_catalogs(): void
    {
        $superadmin = AdminUser::factory()->superadmin()->create();

        foreach (range(1, 10) as $index) {
            Community::create([
                'name' => sprintf('Comunidad paginada %02d', $index),
                'municipality' => 'Municipio de prueba',
                'state' => 'Puebla',
                'default_radius_km' => 8,
                'is_active' => true,
            ]);
            Category::create([
                'name' => sprintf('Rubro paginado %02d', $index),
                'slug' => sprintf('rubro-paginado-%02d', $index),
                'is_active' => true,
            ]);
        }

        foreach (range(1, 9) as $index) {
            $administrator = AdminUser::factory()->create(['email' => "admin-paginado-{$index}@example.test"]);
            $provider = User::factory()->create(['account_type' => 'provider']);
            Vendor::create([
                'user_id' => $provider->id,
                'display_name' => "Proveedor paginado {$index}",
                'slug' => "proveedor-paginado-{$index}",
                'status' => 'active',
            ]);
        }
        foreach (range(1, 16) as $index) {
            AuditLog::create([
                'admin_user_id' => $superadmin->id,
                'action' => 'community.updated',
                'subject_type' => Community::class,
                'subject_id' => $index,
                'created_at' => now()->subSeconds($index),
            ]);
        }

        $response = $this->actingAs($superadmin, 'admin')->get(route('admin.index'));
        $response->assertOk();
        foreach (['communities_page=2', 'categories_page=2', 'audit_page=2'] as $pageParameter) {
            $response->assertSee($pageParameter, false);
        }
    }

    public function test_team_directory_is_separate_from_marketplace_accounts(): void
    {
        $superadmin = AdminUser::factory()->superadmin()->create();
        $candidate = User::factory()->create(['email' => 'cliente@example.test']);
        $staff = AdminUser::factory()->create(['email' => 'equipo@example.test']);
        $this->actingAs($superadmin, 'admin')->get(route('admin.team.index'))
            ->assertOk()->assertSee($staff->email)->assertDontSee($candidate->email)->assertSee('Invitar administrador');
        $this->get(route('admin.users.index'))->assertOk()->assertSee($candidate->email)->assertDontSee($staff->email);
    }

    public function test_admin_can_edit_suspend_and_reactivate_a_community_but_cannot_delete_it(): void
    {
        $admin = AdminUser::factory()->create();
        $community = Community::create([
            'name' => 'Pueblo operativo',
            'municipality' => 'Municipio original',
            'state' => 'Puebla',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')->patch(route('admin.communities.update', $community), [
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
        $superadmin = AdminUser::factory()->superadmin()->create();
        $community = Community::create([
            'name' => 'Comunidad temporal',
            'municipality' => 'Municipio temporal',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($superadmin, 'admin')
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
        $superadmin = AdminUser::factory()->superadmin()->create();
        $community = Community::create([
            'name' => 'Comunidad con historial',
            'municipality' => 'Municipio histórico',
            'default_radius_km' => 8,
            'is_active' => false,
        ]);
        User::factory()->create(['community_id' => $community->id]);

        $this->actingAs($superadmin, 'admin')
            ->delete(route('admin.communities.destroy', $community))
            ->assertRedirect()
            ->assertSessionHasErrors('community');
        $this->assertDatabaseHas('communities', ['id' => $community->id, 'is_active' => false]);
    }

    public function test_last_active_community_cannot_be_suspended(): void
    {
        $admin = AdminUser::factory()->create();
        $community = Community::query()->where('is_active', true)->firstOrFail();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.communities.toggle', $community))
            ->assertRedirect()
            ->assertSessionHasErrors('community');
        $this->assertTrue($community->fresh()->is_active);
    }

    public function test_regular_user_cannot_access_administration(): void
    {
        $client = User::factory()->create();
        $this->actingAs($client, 'web')->get(route('admin.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_remove_a_post_with_reason_audit_and_author_notification(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->create();
        $author = User::factory()->create();
        $author->assignRole(Role::findOrCreate('client'));
        $post = Post::create([
            'user_id' => $author->id,
            'type' => 'business_update',
            'body' => 'Contenido que necesita moderación administrativa.',
            'published_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.posts.remove', $post), ['reason' => 'Incumple las reglas de publicación de la comunidad.'])
            ->assertRedirect();

        $post->refresh();
        $this->assertNotNull($post->removed_at);
        $this->assertSame($admin->id, $post->admin_user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'post.removed', 'subject_id' => $post->id]);
        Notification::assertSentTo($author, MarketplaceActivity::class);

        $this->actingAs($author, 'web')->get(route('dashboard'))->assertDontSee($post->body);
        $this->get(route('profile.show', $author))->assertDontSee($post->body);
        $this->actingAs($admin, 'admin')->get(route('admin.posts.index', ['status' => 'removed']))
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

        $this->actingAs($author, 'web')
            ->patch(route('admin.posts.remove', $post), ['reason' => 'Intento no autorizado de retirar contenido.'])
            ->assertRedirect(route('admin.login'));

        $this->assertNull($post->fresh()->removed_at);
    }
}
