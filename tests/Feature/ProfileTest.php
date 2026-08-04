<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_a_public_profile_without_private_contact_data(): void
    {
        $viewer = User::factory()->withTwoFactorAuthentication()->create(['account_type' => 'client']);
        $provider = User::factory()->withTwoFactorAuthentication()->create([
            'account_type' => 'provider',
            'phone' => '5550001122',
        ]);
        $provider->vendor()->create([
            'display_name' => 'Reparaciones Luna',
            'slug' => 'reparaciones-luna-'.$provider->id,
            'email' => $provider->email,
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

    public function test_provider_can_update_professional_profile(): void
    {
        $provider = User::factory()->withTwoFactorAuthentication()->create(['account_type' => 'provider']);
        $provider->vendor()->create([
            'display_name' => $provider->name,
            'slug' => 'proveedor-'.$provider->id,
            'email' => $provider->email,
        ]);

        $this->actingAs($provider)->put(route('profile.update'), [
            'name' => 'Mario Hernández',
            'phone' => '5551234567',
            'bio' => 'Trabajo con atención y puntualidad.',
            'city' => 'Mi comunidad',
            'display_name' => 'Servicios Mario',
            'description' => 'Reparaciones para el hogar.',
            'specialty' => 'Electricidad',
            'service_area' => 'Centro y alrededores',
            'years_experience' => 8,
            'availability_status' => 'available',
            'certifications' => 'Curso de instalaciones seguras.',
            'tools' => 'Multímetro y herramienta profesional.',
        ])->assertRedirect(route('profile.show', $provider));

        $this->assertDatabaseHas('users', ['id' => $provider->id, 'name' => 'Mario Hernández', 'city' => 'Mi comunidad']);
        $this->assertDatabaseHas('vendors', [
            'user_id' => $provider->id,
            'display_name' => 'Servicios Mario',
            'specialty' => 'Electricidad',
            'years_experience' => 8,
            'availability_status' => 'available',
        ]);
    }

    public function test_profile_displays_user_publications(): void
    {
        $user = User::factory()->withTwoFactorAuthentication()->create(['account_type' => 'client']);
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
