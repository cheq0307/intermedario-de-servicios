<?php

namespace App\Services\Payments;

use App\Contracts\MarketplacePaymentGateway;

final class PaymentGatewayResolver
{
    public function forProvider(string $provider): MarketplacePaymentGateway
    {
        if ($provider === 'stripe') {
            return app(StripeConnectGateway::class);
        }
        if ($provider === 'fake' && (app()->environment(['local', 'testing'])
            || (app()->environment('staging') && config('marketplace.allow_fake_payments')))) {
            return app(FakePaymentGateway::class);
        }
        throw new \RuntimeException('El proveedor del pago no está habilitado en este entorno.');
    }
}
