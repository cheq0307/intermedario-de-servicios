<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MailDeliveryCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_rejects_log_mailer_as_non_deliverable(): void
    {
        config()->set('mail.default', 'log');

        $this->artisan('plaza:mail-check', ['email' => 'persona@example.test'])
            ->expectsOutputToContain('no entrega correos')
            ->assertFailed();
    }

    public function test_command_sends_real_verification_notification_through_configured_transport(): void
    {
        Notification::fake();
        config()->set('mail.default', 'array');
        $user = User::factory()->unverified()->create(['email' => 'persona@example.test']);

        $this->artisan('plaza:mail-check', [
            'email' => $user->email,
            '--verification' => true,
        ])->assertSuccessful();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
