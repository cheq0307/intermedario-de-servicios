<?php

namespace App\Services\Payments;

use App\Contracts\MarketplacePaymentGateway;

final class PaymentGatewayResolver
{
    public function forProvider(string $provider): MarketplacePaymentGateway
    {
        if ($provider === 'stripe') {
            throw_unless(
                config('marketplace.live_payments_enabled'),
                new \RuntimeException('Los pagos reales están deshabilitados en esta versión de Plaza Local.'),
            );

            return app(StripeConnectGateway::class);
        }
        if ($provider === 'fake' && (app()->environment(['local', 'testing'])
            || (app()->environment('staging') && config('marketplace.allow_fake_payments')))) {
            return app(FakePaymentGateway::class);
        }
        throw new \RuntimeException('El proveedor del pago no está habilitado en este entorno.');
    }
}
