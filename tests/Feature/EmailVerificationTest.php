<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_receives_verification_email_and_can_enter_dashboard(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'account_type' => 'client',
            'name' => 'Persona Nueva',
            'email' => 'persona@example.test',
            'password' => 'Seguro123',
            'password_confirmation' => 'Seguro123',
        ]);

        $user = User::where('email', 'persona@example.test')->firstOrFail();
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Verifica tu correo');
    }

    public function test_user_can_verify_email_from_signed_link(): void
    {
        $user = User::factory()->unverified()->create(['account_type' => 'client']);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect('/dashboard?verified=1');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_cannot_publish(): void
    {
        $user = User::factory()->unverified()->create(['account_type' => 'client']);

        $this->actingAs($user)
            ->post(route('posts.store'), [])
            ->assertRedirect(route('verification.notice'));
    }

    public function test_user_can_request_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
