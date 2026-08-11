<?php

namespace App\Http\Controllers;

use App\Jobs\FinalizeMarketplacePayment;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), (string) config('services.stripe.webhook_secret'));
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response('invalid webhook', 400);
        }
        DB::transaction(function () use ($event): void {
            $record = DB::table('payment_webhook_events')->where('event_id', $event->id)->lockForUpdate()->first();
            if ($record?->processed_at) {
                return;
            }
            if (! $record) {
                DB::table('payment_webhook_events')->insertOrIgnore(['provider' => 'stripe', 'event_id' => $event->id, 'event_type' => $event->type, 'payload' => json_encode($event->toArray()), 'created_at' => now(), 'updated_at' => now()]);
            }
            $object = $event->data->object;
            if (in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
                $payment = Payment::where('provider', 'stripe')->where('provider_reference', $object->id)->lockForUpdate()->first();
                if ($payment && $event->type === 'payment_intent.succeeded') {
                    $payment->update(['status' => 'paid', 'method' => $object->payment_method_types[0] ?? 'card', 'paid_at' => now()]);
                    if ($payment->order->status->value === 'awaiting_payment') {
                        $payment->order()->update(['status' => 'paid']);
                        $payment->order->inventoryReservation?->update(['status' => 'consumed', 'consumed_at' => now()]);
                    } else {
                        $payment->update(['status' => 'refund_pending']);
                        FinalizeMarketplacePayment::dispatch($payment->id, 'refund')->afterCommit();
                    }
                } elseif ($payment) {
                    $payment->update(['status' => 'failed']);
                }
            }
            DB::table('payment_webhook_events')->where('event_id', $event->id)->update(['processed_at' => now(), 'updated_at' => now()]);
        });

        return response('ok');
    }
}
