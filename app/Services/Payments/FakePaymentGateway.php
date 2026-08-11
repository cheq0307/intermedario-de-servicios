<?php

namespace App\Services\Payments;

use App\Contracts\MarketplacePaymentGateway;
use App\Models\Order;
use App\Models\Payment;
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

    public function releasePayment(Payment $payment): void
    {
        $payment->update(['status' => 'released', 'released_at' => now()]);
    }

    public function refundPayment(Payment $payment): void
    {
        $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
    }
}
