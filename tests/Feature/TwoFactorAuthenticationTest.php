<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_explains_password_requirements(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Al menos 8 caracteres')
            ->assertSee('Incluye una letra')
            ->assertSee('data-password-rule="number"', false)
            ->assertSee('data-password-rule="match"', false);
    }

    public function test_security_settings_require_authentication(): void
    {
        $this->get('/seguridad')->assertRedirect('/login');

        $this->actingAs(User::factory()->create())
            ->get('/seguridad')
            ->assertRedirect('/user/confirm-password');

        $this->withSession(['auth.password_confirmed_at' => time()])
            ->get('/seguridad')
            ->assertOk()
            ->assertSee('Autenticación en dos pasos');
    }

    public function test_security_setup_displays_the_unconfirmed_secret(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $user = User::factory()->create([
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['recovery-code'])),
            'two_factor_confirmed_at' => null,
        ]);

        $this->actingAs($user)
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/seguridad')
            ->assertOk()
            ->assertSee($secret);
    }

    public function test_users_must_configure_two_factor_before_using_the_marketplace(): void
    {
        $this->actingAs(User::factory()->create())
            ->withSession(['auth.password_confirmed_at' => time()])
            ->get('/dashboard')
            ->assertRedirect('/seguridad');

        $this->actingAs(User::factory()->withTwoFactorAuthentication()->create(['account_type' => 'client']))
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_user_with_two_factor_enabled_must_enter_a_valid_code(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = User::factory()->create([
            'password' => 'Seguro123',
            'two_factor_secret' => Crypt::encrypt($secret),
            'two_factor_recovery_codes' => Crypt::encrypt(json_encode(['codigo-de-respaldo'])),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Seguro123',
        ])->assertRedirect('/two-factor-challenge');

        $this->assertGuest();

        $this->post('/two-factor-challenge', [
            'code' => $google2fa->getCurrentOtp($secret),
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
    }
}
