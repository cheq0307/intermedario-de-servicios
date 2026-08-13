<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_form_offers_postal_lookup_and_identifies_community_postal_codes(): void
    {
        $community = Community::create([
            'name' => 'Centro',
            'municipality' => 'San Matías Tlalancaleca',
            'state' => 'Puebla',
            'postal_code' => '74140',
            'default_radius_km' => 8,
            'is_active' => true,
        ]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-postal-assistant', false)
            ->assertSee('data-postal-input', false)
            ->assertSee('data-community-select', false)
            ->assertSee('data-postal-code="74140"', false)
            ->assertSee((string) $community->id, false);
    }

    public function test_password_screens_render_explicit_visibility_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle="login-password"', false)
            ->assertSee('data-password-toggle-label', false)
            ->assertSee('Mostrar');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-password-toggle="register-password"', false)
            ->assertSee('data-password-toggle="register-password-confirmation"', false);

        $this->get(route('password.reset', ['token' => 'token-de-prueba', 'email' => 'persona@example.test']))
            ->assertOk()
            ->assertSee('data-password-toggle="reset-password"', false)
            ->assertSee('data-password-toggle="reset-password-confirmation"', false);

        $user = User::factory()->create();
        $this->actingAs($user)
            ->get(route('password.confirm'))
            ->assertOk()
            ->assertSee('data-password-toggle="confirm-current-password"', false);
    }

    public function test_client_can_register_and_reach_dashboard(): void
    {
        $response = $this->post('/register', [
            'account_type' => 'client',
            'community_id' => Community::query()->value('id'),
            'name' => 'Cliente Ejemplo',
            'email' => 'cliente@example.test',
            'password' => 'Seguro123',
            'password_confirmation' => 'Seguro123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'cliente@example.test',
            'account_type' => 'client',
        ]);
    }

    public function test_registration_creates_one_user_without_premature_commercial_approval(): void
    {
        $response = $this->post('/register', [
            'community_id' => Community::query()->value('id'),
            'name' => 'Carpintería Ramírez',
            'email' => 'carpinteria@example.test',
            'phone' => '5551234567',
            'password' => 'Seguro123',
            'password_confirmation' => 'Seguro123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'carpinteria@example.test', 'account_type' => 'client']);
        $this->assertDatabaseMissing('vendors', ['user_id' => auth()->id()]);
        $this->assertTrue(auth()->user()->hasRole('client'));
    }

    public function test_duplicate_email_error_is_clear_and_in_spanish(): void
    {
        User::factory()->create(['email' => 'registrado@example.test']);

        $response = $this->from('/register')->post('/register', [
            'account_type' => 'client',
            'community_id' => Community::query()->value('id'),
            'name' => 'Cuenta duplicada',
            'email' => 'registrado@example.test',
            'password' => 'Seguro123',
            'password_confirmation' => 'Seguro123',
        ]);

        $response
            ->assertRedirect('/register')
            ->assertSessionHasErrors([
                'email' => 'Este correo ya está registrado. Inicia sesión o recupera tu contraseña.',
            ]);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'Seguro123']);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Seguro123',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
}
