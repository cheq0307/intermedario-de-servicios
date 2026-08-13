<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Community;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminVendorReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_reviews_private_provider_dossier_before_deciding(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $community = Community::create([
            'name' => 'Centro', 'municipality' => 'Huejotzingo', 'state' => 'Puebla',
            'postal_code' => '74140', 'default_radius_km' => 8, 'is_active' => true,
        ]);
        $provider = User::factory()->create([
            'name' => 'Persona Proveedora', 'phone' => '2221234567', 'community_id' => $community->id,
        ]);
        $vendor = Vendor::create([
            'user_id' => $provider->id, 'display_name' => 'Servicio Local', 'slug' => 'servicio-local',
            'description' => 'Descripción profesional suficiente.', 'specialty' => 'Alimentos', 'service_area' => 'Centro',
            'years_experience' => 4, 'certifications' => 'Manejo higiénico', 'tools' => 'Equipo propio',
            'business_hours' => ['days' => ['monday'], 'opens_at' => '09:00', 'closes_at' => '18:00'],
            'status' => 'pending', 'submitted_at' => now(),
        ]);
        $category = Category::query()->firstOrFail();
        $vendor->categories()->attach($category);

        $this->actingAs($admin)
            ->get(route('admin.vendors.show', $vendor))
            ->assertOk()
            ->assertSee('Expediente administrativo privado')
            ->assertSee('Persona Proveedora')
            ->assertSee('2221234567')
            ->assertSee('Manejo higiénico')
            ->assertSee($category->name)
            ->assertSee('Aprobar proveedor')
            ->assertSee('Solicitar cambios');
    }

    public function test_regular_user_cannot_open_private_provider_dossier(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $user->id, 'display_name' => 'Privado', 'slug' => 'privado', 'status' => 'draft',
        ]);

        $this->actingAs($user)->get(route('admin.vendors.show', $vendor))->assertForbidden();
    }

    public function test_manual_community_becomes_a_reusable_postal_reference(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($admin)->post(route('admin.communities.store'), [
            'name' => 'Santa Ana', 'municipality' => 'Municipio Local', 'state' => 'Puebla',
            'postal_code' => '74140', 'default_radius_km' => 8,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('postal_codes', [
            'postal_code' => '74140', 'settlement' => 'Santa Ana', 'municipality' => 'Municipio Local',
        ]);
        $this->getJson(route('postal-codes.show', '74140'))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('places.0.settlement', 'Santa Ana');
    }
}
