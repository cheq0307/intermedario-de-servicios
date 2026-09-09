<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use App\Notifications\AdminResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UnifiedLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_login_authenticates_superadmin_without_marketplace_access(): void
    {
        $admin = AdminUser::factory()->superadmin()->create(['password' => 'AdminSeguro123', 'phone_verified_at' => null]);
        $this->get(route('admin.login'))->assertRedirect(route('login'));
        $this->post('/login', ['email' => $admin->email, 'password' => 'AdminSeguro123'])->assertRedirect(route('admin.verification.notice'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertGuest('web');
        $this->get(route('admin.index'))->assertRedirect(route('admin.verification.notice'));
    }

    public function test_matching_email_uses_password_to_select_identity(): void
    {
        $admin = AdminUser::factory()->create(['email' => 'dual@example.test', 'password' => 'AdminSeguro123']);
        $user = User::factory()->create(['email' => $admin->email, 'password' => 'ClienteSeguro123']);
        $this->post('/login', ['email' => $user->email, 'password' => 'ClienteSeguro123'])->assertRedirect();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
        $this->post('/login', ['email' => $admin->email, 'password' => 'AdminSeguro123'])->assertRedirect(route('admin.verification.notice'));
        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_suspended_admin_and_wrong_password_cannot_enter(): void
    {
        $admin = AdminUser::factory()->create(['active' => false, 'password' => 'AdminSeguro123']);
        foreach (['AdminSeguro123', 'Incorrecta123'] as $password) {
            $this->post('/login', ['email' => $admin->email, 'password' => $password])->assertSessionHasErrors('email');
            $this->assertGuest('admin');
            $this->assertGuest('web');
        }
    }

    public function test_common_recovery_sends_administrative_reset_link(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->create();
        $this->post('/forgot-password', ['email' => $admin->email])->assertSessionHas('status');
        Notification::assertSentTo($admin, AdminResetPassword::class);
    }

    public function test_ambiguous_credentials_never_select_an_administrator(): void
    {
        $admin = AdminUser::factory()->create(['email' => 'duplicate@example.test', 'password' => 'MismaClave123']);
        User::factory()->create(['email' => $admin->email, 'password' => 'MismaClave123']);
        $this->post('/login', ['email' => $admin->email, 'password' => 'MismaClave123'])->assertSessionHasErrors('email');
        $this->assertGuest('admin');
        $this->assertGuest('web');
    }

    public function test_common_login_is_rate_limited_for_administrators(): void
    {
        $admin = AdminUser::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $admin->email, 'password' => 'Incorrecta123'])->assertSessionHasErrors('email');
        }
        $this->post('/login', ['email' => $admin->email, 'password' => 'Incorrecta123'])->assertStatus(429);
        $this->assertGuest('admin');
    }
}
