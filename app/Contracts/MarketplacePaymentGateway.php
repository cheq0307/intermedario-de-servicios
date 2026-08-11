<?php

namespace App\Contracts;

use App\Models\Order;
use App\Models\Payment;

interface MarketplacePaymentGateway
{
    /** @return array{provider: string, reference: string, payload: array<string, mixed>} */
    public function createPayment(Order $order): array;

    public function releasePayment(Payment $payment): void;

    public function refundPayment(Payment $payment): void;
}
