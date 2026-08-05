<?php

namespace App\Contracts;

use App\Models\Order;

interface MarketplacePaymentGateway
{
    /** @return array{provider: string, reference: string, payload: array<string, mixed>} */
    public function createPayment(Order $order): array;
}
