<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizeMarketplacePayment;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! config('services.stripe.webhook_secret')) {
            return response('webhook not configured', 503);
        }
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), (string) config('services.stripe.webhook_secret'));
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('invalid webhook', 400);
        }
        if ($event->livemode !== (bool) config('services.stripe.livemode')) {
            return response('incorrect payment environment', 400);
        }
        DB::transaction(function () use ($event): void {
            DB::table('payment_webhook_events')->insertOrIgnore(['provider' => 'stripe', 'event_id' => $event->id, 'event_type' => $event->type, 'payload' => json_encode($event->toArray()), 'created_at' => now(), 'updated_at' => now()]);
            $record = DB::table('payment_webhook_events')->where('event_id', $event->id)->lockForUpdate()->first();
            if ($record?->processed_at) {
                return;
            }
            $object = $event->data->object;
            if (in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
                $this->reconcileIntent($object, $event->type);
            } elseif (in_array($event->type, ['refund.created', 'refund.updated', 'refund.failed'], true)) {
                $this->reconcileRefund($object);
            }
            DB::table('payment_webhook_events')->where('event_id', $event->id)->update(['processed_at' => now(), 'updated_at' => now()]);
        });

        return response('ok');
    }

    private function reconcileIntent(object $intent, string $type): void
    {
        $reference = Payment::where('provider', 'stripe')->where('provider_reference', $intent->id)->first();
        // Creation may still be committing. Do not acknowledge an unassociated payment.
        abort_unless($reference, 503, 'Payment association not available yet.');
        $order = Order::lockForUpdate()->findOrFail($reference->order_id);
        $payment = Payment::lockForUpdate()->findOrFail($reference->id);
        if (! in_array($payment->status->value, ['pending', 'authorized', 'failed', 'cancelled'], true)) {
            return;
        }
        if ($type === 'payment_intent.payment_failed') {
            if (in_array($payment->status->value, ['pending', 'authorized'], true)) {
                $payment->update(['status' => 'failed']);
            }

            return;
        }
        if (($intent->amount_received ?? null) !== $payment->gross_amount
            || strtoupper((string) ($intent->currency ?? '')) !== $payment->currency
            || $payment->gross_amount !== $order->total_amount
            || $payment->currency !== $order->currency) {
            Log::error('Stripe payment amount/currency mismatch.', ['payment_id' => $payment->id]);

            return;
        }
        if (! in_array($order->status->value, ['awaiting_payment', 'cancelled'], true)) {
            Log::error('Unexpected order state for first Stripe confirmation.', ['payment_id' => $payment->id, 'order_id' => $order->id]);

            return;
        }
        $payment->update(['status' => 'paid', 'method' => $intent->payment_method_types[0] ?? 'card', 'paid_at' => now()]);
        if ($order->status->value === 'cancelled') {
            $payment->update(['status' => 'refund_pending']);
            FinalizeMarketplacePayment::dispatch($payment->id, 'refund')->afterCommit();

            return;
        }
        $order->update(['status' => 'paid']);
        $order->inventoryReservation?->update(['status' => 'consumed', 'consumed_at' => now()]);
    }

    private function reconcileRefund(object $refund): void
    {
        $reference = Payment::where('provider', 'stripe')->where('provider_reference', $refund->payment_intent ?? '')->first();
        abort_unless($reference, 503, 'Payment association not available yet.');
        Order::lockForUpdate()->findOrFail($reference->order_id);
        $payment = Payment::lockForUpdate()->findOrFail($reference->id);
        if ($payment->status->value !== 'refund_pending') {
            return;
        }
        // Only reconcile the full refund initiated by this application.
        // Dashboard/partial refunds require a separate operational review.
        $refundId = $payment->provider_payload['refund_id'] ?? null;
        abort_unless($refundId, 503, 'Refund association not available yet.');
        if ($refundId !== $refund->id || ($refund->amount ?? null) !== $payment->gross_amount
            || strtoupper((string) ($refund->currency ?? '')) !== $payment->currency) {
            Log::error('Stripe refund mismatch.', ['payment_id' => $payment->id]);

            return;
        }
        if ($refund->status === 'succeeded') {
            $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
        } elseif (in_array($refund->status, ['failed', 'canceled'], true)) {
            $payment->update(['provider_payload' => [...($payment->provider_payload ?? []), 'refund_status' => $refund->status]]);
            Log::error('Stripe refund requires manual recovery.', ['payment_id' => $payment->id]);
        }
    }
}
