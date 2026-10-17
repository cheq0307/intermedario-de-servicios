<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentDisabledProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_provider_callbacks_remain_disabled_even_with_live_environment_flags(): void
    {
        config([
            'marketplace.payments_enabled' => false,
            'marketplace.live_payments_enabled' => true,
        ]);

        $this->postJson(route('stripe.webhook'), [])->assertStatus(503);
        $this->postJson(route('mercadopago.webhook'), [])->assertStatus(503);
    }

    public function test_paid_promotions_and_payout_onboarding_are_inaccessible(): void
    {
        config([
            'marketplace.payments_enabled' => false,
            'marketplace.live_payments_enabled' => true,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->get(route('promotions.index'))->assertStatus(503);
        $this->actingAs($user, 'web')->post(route('stripe.connect'))->assertStatus(503);
    }
}
