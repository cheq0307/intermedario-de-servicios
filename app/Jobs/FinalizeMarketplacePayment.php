<?php

namespace App\Jobs;

use App\Contracts\MarketplacePaymentGateway;
use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeMarketplacePayment implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public readonly int $paymentId, public readonly string $action) {}

    public function handle(MarketplacePaymentGateway $gateway): void
    {
        $payment = Payment::findOrFail($this->paymentId);
        if ($this->action === 'release' && $payment->status->value === 'release_pending') {
            $gateway->releasePayment($payment);
        }
        if ($this->action === 'refund' && $payment->status->value === 'refund_pending') {
            $gateway->refundPayment($payment);
        }
    }
}
