<?php

namespace App\Services\Payments;

use App\Contracts\MarketplacePaymentGateway;
use App\Models\Order;
use App\Models\Payment;
use Stripe\StripeClient;

class StripeConnectGateway implements MarketplacePaymentGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createPayment(Order $order): array
    {
        $order->loadMissing('vendor');
        throw_unless($order->vendor->stripe_account_id && $order->vendor->stripe_payouts_enabled, new \RuntimeException('El proveedor debe completar la verificacion de Stripe antes de recibir pedidos.'));
        $intent = $this->stripe->paymentIntents->create([
            'amount' => $order->total_amount,
            'currency' => strtolower($order->currency),
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => ['order_id' => $order->id, 'order_public_id' => $order->public_id],
            'transfer_group' => 'ORDER_'.$order->public_id,
        ], ['idempotency_key' => 'order-payment-'.$order->public_id]);

        return ['provider' => 'stripe', 'reference' => $intent->id, 'payload' => ['client_secret' => $intent->client_secret, 'status' => $intent->status]];
    }

    public function releasePayment(Payment $payment): void
    {
        $payment->loadMissing('order.vendor');
        $account = $payment->order->vendor->stripe_account_id;
        throw_unless($account && $payment->order->vendor->stripe_payouts_enabled, new \RuntimeException('El proveedor no tiene depositos Stripe habilitados.'));
        $transfer = $this->stripe->transfers->create([
            'amount' => $payment->vendor_net_amount,
            'currency' => strtolower($payment->currency),
            'destination' => $account,
            'transfer_group' => 'ORDER_'.$payment->order->public_id,
            'metadata' => ['order_id' => $payment->order_id, 'payment_id' => $payment->id],
        ], ['idempotency_key' => 'release-payment-'.$payment->id]);
        $payment->update(['status' => 'released', 'released_at' => now(), 'provider_payload' => [...($payment->provider_payload ?? []), 'transfer_id' => $transfer->id]]);
    }

    public function refundPayment(Payment $payment): void
    {
        $refund = $this->stripe->refunds->create(['payment_intent' => $payment->provider_reference, 'metadata' => ['payment_id' => $payment->id]], ['idempotency_key' => 'refund-payment-'.$payment->id]);
        $payment->update(['status' => $refund->status === 'succeeded' ? 'refunded' : 'refund_pending', 'refunded_at' => $refund->status === 'succeeded' ? now() : null, 'provider_payload' => [...($payment->provider_payload ?? []), 'refund_id' => $refund->id]]);
    }
}
