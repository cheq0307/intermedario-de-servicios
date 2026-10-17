<?php

namespace Tests\Feature;

use App\Contracts\MarketplacePaymentGateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivePaymentsDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_real_payment_gateway_is_unavailable_in_this_release(): void
    {
        config([
            'marketplace.live_payments_enabled' => false,
            'marketplace.payment_driver' => 'stripe',
            'services.stripe.secret' => 'sk_test_not-real',
        ]);
        $this->app->forgetInstance(MarketplacePaymentGateway::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Los pagos reales están deshabilitados');

        $this->app->make(MarketplacePaymentGateway::class);
    }

    public function test_live_payment_routes_return_service_unavailable_when_disabled(): void
    {
        config(['marketplace.live_payments_enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->post('/stripe/conectar')
            ->assertServiceUnavailable();
        $this->post('/webhooks/stripe')->assertServiceUnavailable();
        $this->post('/webhooks/mercado-pago')->assertServiceUnavailable();
    }
}
