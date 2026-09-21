<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use App\Services\Accounts\SmsVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PausedPhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['phone_verification.enabled' => false, 'phone_verification.account_sid' => 'fixture',
            'phone_verification.auth_token' => 'fixture', 'phone_verification.service_sid' => 'fixture']);
        Http::preventStrayRequests();
        Http::fake();
    }

    public function test_client_can_save_phone_without_sms_or_false_verification(): void
    {
        $user = User::factory()->create(['phone_verified_at' => now()]);
        $this->actingAs($user, 'web')->get(route('phone.show'))->assertOk()
            ->assertSee('Guardar celular')->assertDontSee('Enviar código por SMS')->assertDontSee('Código de 4 dígitos');
        $this->post(route('phone.send'), ['phone' => '2221234567'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2221234567', $user->fresh()->phone);
        $this->assertNull($user->fresh()->phone_verified_at);
        $this->post(route('phone.verify'), ['code' => '1234'])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_required_format_and_uniqueness_still_apply(): void
    {
        $user = User::factory()->create(['phone' => '2221111111', 'phone_verified_at' => null]);
        User::factory()->create(['phone' => '2222222222']);
        $this->actingAs($user, 'web');
        foreach (['', '123', '2222222222'] as $phone) {
            $this->post(route('phone.send'), ['phone' => $phone])->assertSessionHasErrors('phone');
        }
        $this->assertSame('2221111111', $user->fresh()->phone);
        Http::assertNothingSent();
    }

    public function test_admin_can_save_phone_and_access_with_verified_email_but_reactivation_requires_sms(): void
    {
        $admin = AdminUser::factory()->create(['phone_verified_at' => null]);
        $this->actingAs($admin, 'admin')->post(route('admin.phone.send'), ['phone' => '2223333333'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2223333333', $admin->fresh()->phone);
        $this->assertNull($admin->fresh()->phone_verified_at);
        $this->get(route('admin.index'))->assertOk();
        $this->get(route('admin.verification.notice'))->assertRedirect(route('admin.index'));
        config(['phone_verification.enabled' => true]);
        $this->get(route('admin.index'))->assertRedirect(route('admin.verification.notice'));
        config(['phone_verification.enabled' => false]);
        $admin->forceFill(['email_verified_at' => null])->save();
        $this->get(route('admin.index'))->assertRedirect(route('admin.verification.notice'));
        Http::assertNothingSent();
    }

    public function test_paused_service_cannot_send_even_with_credentials(): void
    {
        $sms = app(SmsVerification::class);
        $this->assertFalse($sms->configured());
        try {
            $sms->send('2221234567');
            $this->fail('SMS should be blocked while paused.');
        } catch (ValidationException) {
            Http::assertNothingSent();
        }
    }
}
