<?php

namespace App\Services\Payments;

use App\Contracts\MarketplacePaymentGateway;
use App\Models\Order;
use Illuminate\Support\Str;

class FakePaymentGateway implements MarketplacePaymentGateway
{
    public function createPayment(Order $order): array
    {
        $allowed = app()->environment(['local', 'testing']) || (app()->environment('staging') && config('marketplace.allow_fake_payments'));
        abort_unless($allowed, 503, 'El simulador de pagos está deshabilitado en este entorno.');

        return [
            'provider' => 'fake',
            'reference' => 'fake_'.Str::uuid(),
            'payload' => ['simulated' => true, 'order' => $order->public_id],
        ];
    }
}
