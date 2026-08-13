<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_and_filter_the_user_directory(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $community = Community::create([
            'name' => 'San Miguel',
            'municipality' => 'Puebla',
            'state' => 'Puebla',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);
        User::factory()->create([
            'name' => 'Persona Encontrable',
            'email' => 'encontrable@example.test',
            'community_id' => $community->id,
        ]);
        User::factory()->create(['name' => 'Persona Distinta']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'encontrable', 'community_id' => $community->id]))
            ->assertOk()
            ->assertSee('Persona Encontrable')
            ->assertDontSee('Persona Distinta')
            ->assertSee('Directorio administrativo');
    }

    public function test_admin_can_filter_the_provider_directory_by_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $pendingUser = User::factory()->create(['account_type' => 'provider']);
        $activeUser = User::factory()->create(['account_type' => 'provider']);
        Vendor::create(['user_id' => $pendingUser->id, 'display_name' => 'Proveedor Pendiente', 'slug' => 'proveedor-pendiente', 'status' => 'pending']);
        Vendor::create(['user_id' => $activeUser->id, 'display_name' => 'Proveedor Activo', 'slug' => 'proveedor-activo', 'status' => 'active']);

        $this->actingAs($admin)
            ->get(route('admin.vendors.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('Proveedor Pendiente')
            ->assertDontSee('Proveedor Activo')
            ->assertSee('Directorio administrativo');
    }

    public function test_dashboard_metrics_link_to_management_directories(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->syncRoles([Role::findOrCreate('superadmin')]);

        $this->actingAs($superadmin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee(route('admin.users.index'), false)
            ->assertSee(route('admin.vendors.index'), false)
            ->assertSee('Abrir directorio');
    }

    public function test_regular_user_cannot_access_management_directories(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.vendors.index'))->assertForbidden();
    }

    public function test_communities_may_share_a_name_when_their_municipalities_differ(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        Community::create([
            'name' => 'Centro',
            'municipality' => 'Municipio Uno',
            'state' => 'Puebla',
            'postal_code' => '72000',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.communities.store'), [
                'name' => 'Centro',
                'municipality' => 'Municipio Dos',
                'state' => 'Puebla',
                'postal_code' => '75000',
                'default_radius_km' => 8,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('communities', [
            'name' => 'Centro',
            'municipality' => 'Municipio Dos',
            'postal_code' => '75000',
        ]);
    }
}
