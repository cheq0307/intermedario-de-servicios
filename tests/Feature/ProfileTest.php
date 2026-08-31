<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Community;
use App\Models\Post;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_public_profile_but_must_register_to_contact(): void
    {
        $provider = User::factory()->create(['email_verified_at' => now()]);
        $provider->vendor()->create([
            'display_name' => 'Proveedor público',
            'slug' => 'proveedor-publico',
            'status' => 'active',
        ]);

        $this->get(route('profile.show', $provider))
            ->assertOk()
            ->assertSee('Proveedor público')
            ->assertSee('Regístrate para contactar')
            ->assertSee(route('register'), false);
    }

    public function test_authenticated_user_can_view_a_public_profile_without_private_contact_data(): void
    {
        $viewer = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create([
            'account_type' => 'provider',
            'phone' => '5550001122',
        ]);
        $provider->vendor()->create([
            'display_name' => 'Reparaciones Luna',
            'slug' => 'reparaciones-luna-'.$provider->id,
            'email' => $provider->email,
            'status' => 'active',
            'phone' => $provider->phone,
            'specialty' => 'Plomería',
            'availability_status' => 'available',
        ]);

        $this->actingAs($viewer)
            ->get(route('profile.show', $provider))
            ->assertOk()
            ->assertSee('Reparaciones Luna')
            ->assertSee('Plomería')
            ->assertDontSee('5550001122')
            ->assertDontSee($provider->email);
    }

    public function test_profile_owner_can_see_their_own_email_address(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($user)
            ->get(route('profile.show', $user))
            ->assertOk()
            ->assertSee('Correo:')
            ->assertSee($user->email);
    }

    public function test_public_profile_shows_location_without_the_redundant_capability_phrase(): void
    {
        $community = Community::create([
            'name' => 'San Juan Tetla',
            'municipality' => 'Chiautzingo',
            'state' => 'Puebla',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['community_id' => $community->id]);

        $this->get(route('profile.show', $user))
            ->assertOk()
            ->assertSee('San Juan Tetla')
            ->assertSee('Chiautzingo, Puebla')
            ->assertDontSee('Compra, solicita y participa en la comunidad')
            ->assertDontSee('Ofrece productos o servicios')
            ->assertDontSee('radio local');
    }

    public function test_administrator_can_see_email_for_account_support(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $user = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($admin)
            ->get(route('profile.show', $user))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_provider_can_update_professional_profile(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $provider->vendor()->create([
            'display_name' => $provider->name,
            'slug' => 'proveedor-'.$provider->id,
            'email' => $provider->email,
        ]);

        $community = Community::create(['name' => 'Centro', 'municipality' => 'Mi comunidad', 'default_radius_km' => 8, 'is_active' => true]);

        $this->actingAs($provider)->put(route('profile.update'), [
            'offered_categories' => [Category::query()->value('id')],
            'name' => 'Mario Hernández',
            'phone' => '5551234567',
            'bio' => 'Trabajo con atención y puntualidad.',
            'community_id' => $community->id,
            'display_name' => 'Servicios Mario',
            'description' => 'Reparaciones para el hogar.',
            'specialty' => 'Electricidad',
            'service_area' => 'Centro y alrededores',
            'years_experience' => 8,
            'availability_status' => 'busy',
            'business_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'business_opens_at' => '09:00',
            'business_closes_at' => '18:00',
            'certifications' => 'Curso de instalaciones seguras.',
            'tools' => 'Multímetro y herramienta profesional.',
        ])->assertRedirect(route('profile.show', $provider));

        $this->assertDatabaseHas('users', ['id' => $provider->id, 'name' => 'Mario Hernández', 'city' => 'Mi comunidad', 'community_id' => $community->id]);
        $this->assertDatabaseHas('vendors', [
            'user_id' => $provider->id,
            'display_name' => 'Servicios Mario',
            'specialty' => 'Electricidad',
            'years_experience' => 8,
            'availability_status' => 'busy',
        ]);
        $this->assertSame([
            'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            'opens_at' => '09:00',
            'closes_at' => '18:00',
            'timezone' => 'America/Mexico_City',
        ], $provider->vendor->fresh()->business_hours);
    }

    public function test_query_string_cannot_enable_or_create_a_commercial_profile(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($user)
            ->get(route('profile.edit', ['ofrecer' => 1]))
            ->assertOk()
            ->assertSee('Agregar mis servicios')
            ->assertSee(route('capabilities.activate', 'provider'), false)
            ->assertDontSee('Servicios, productos o actividades que ofreces');

        $this->assertDatabaseMissing('vendors', ['user_id' => $user->id]);
    }

    public function test_forged_profile_fields_cannot_create_a_commercial_profile(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);
        $community = Community::query()->firstOrFail();

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Cuenta actualizada',
            'community_id' => $community->id,
            'offers_services' => 1,
            'display_name' => 'Proveedor inyectado',
            'offered_categories' => [Category::query()->value('id')],
            'availability_status' => 'available',
            'business_days' => ['monday'],
            'business_opens_at' => '09:00',
            'business_closes_at' => '18:00',
        ])->assertRedirect(route('profile.show', $user));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Cuenta actualizada']);
        $this->assertDatabaseMissing('vendors', ['user_id' => $user->id]);
    }

    public function test_interests_can_be_updated_without_revalidating_legacy_profile_fields(): void
    {
        $user = User::factory()->create([
            'account_type' => 'client',
            'phone' => '22216272334443',
        ]);
        $selected = Category::query()->firstOrCreate(
            ['slug' => 'comida-bebidas'],
            ['name' => 'Comida y bebidas', 'is_active' => true],
        );
        $behaviorOnly = Category::query()->firstOrCreate(
            ['slug' => 'tecnologia'],
            ['name' => 'Tecnología', 'is_active' => true],
        );
        $user->categoryPreferences()->sync([
            $behaviorOnly->id => ['interest_score' => 100, 'behavior_score' => 35],
        ]);

        $this->actingAs($user)
            ->put(route('profile.interests.update'), ['interests' => [$selected->id]])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'phone' => '22216272334443']);
        $this->assertDatabaseHas('user_category_preferences', [
            'user_id' => $user->id,
            'category_id' => $selected->id,
            'interest_score' => 100,
            'behavior_score' => 0,
        ]);
        $this->assertDatabaseHas('user_category_preferences', [
            'user_id' => $user->id,
            'category_id' => $behaviorOnly->id,
            'interest_score' => 0,
            'behavior_score' => 35,
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(route('profile.interests.update'), false)
            ->assertSee('form="profile-interests-form"', false)
            ->assertSee('Guardar intereses');
    }

    public function test_administrative_profile_hides_paused_commercial_information(): void
    {
        $provider = User::factory()->create(['account_type' => 'provider']);
        $provider->assignRole(Role::findOrCreate('client'));
        $provider->assignRole(Role::findOrCreate('admin'));
        $vendor = $provider->vendor()->create([
            'display_name' => 'Servicios Múltiples',
            'slug' => 'servicios-multiples-'.$provider->id,
            'availability_status' => 'busy',
            'status' => 'active',
            'business_hours' => [
                'days' => ['monday'],
                'opens_at' => '09:00',
                'closes_at' => '18:00',
                'timezone' => 'America/Mexico_City',
            ],
        ]);

        $moment = CarbonImmutable::parse('2026-08-10 10:00', 'America/Mexico_City');
        $this->assertTrue($vendor->isWithinBusinessHours($moment));
        $this->assertFalse($vendor->isWithinBusinessHours($moment->setTime(20, 0)));

        $this->actingAs($provider)
            ->get(route('profile.show', $provider))
            ->assertOk()
            ->assertSee('Cuenta institucional de Plaza Local')
            ->assertSee('Administrador')
            ->assertDontSee('Realizando un trabajo')
            ->assertDontSee('Horario: Lun');
    }

    public function test_profile_displays_user_publications(): void
    {
        $user = User::factory()->create(['account_type' => 'client']);
        Post::create([
            'user_id' => $user->id,
            'type' => 'job_request',
            'body' => 'Necesito ayuda para reparar una puerta.',
            'published_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.show', $user))
            ->assertOk()
            ->assertSee('Necesito ayuda para reparar una puerta.');
    }
}
