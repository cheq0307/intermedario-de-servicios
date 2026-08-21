<?php

namespace App\Http\Controllers;

use App\Services\Payments\MercadoPagoPromotionCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use MercadoPago\Exceptions\InvalidWebhookSignatureException;
use MercadoPago\Webhook\WebhookSignatureValidator;

class MercadoPagoWebhookController extends Controller
{
    public function __invoke(Request $request, MercadoPagoPromotionCheckout $checkout): Response
    {
        $secret = (string) config('services.mercadopago.webhook_secret');
        if ($secret === '') {
            return response('webhook not configured', 503);
        }

        $dataId = (string) ($request->query('data.id') ?: data_get($request->all(), 'data.id'));

        try {
            WebhookSignatureValidator::validate(
                $request->header('x-signature'),
                $request->header('x-request-id'),
                $dataId,
                $secret,
                300,
            );
        } catch (InvalidWebhookSignatureException|\InvalidArgumentException) {
            return response('invalid webhook', 401);
        }

        if ($request->input('type') !== 'payment' || ! ctype_digit($dataId)) {
            return response('ok');
        }

        $notificationId = (string) ($request->input('id') ?: $request->header('x-request-id'));
        if ($notificationId === '') {
            return response('missing notification id', 400);
        }

        $eventId = 'mercadopago:'.$notificationId;
        $alreadyProcessed = DB::table('payment_webhook_events')
            ->where('event_id', $eventId)
            ->whereNotNull('processed_at')
            ->exists();

        if ($alreadyProcessed) {
            return response('ok');
        }

        DB::table('payment_webhook_events')->insertOrIgnore([
            'provider' => 'mercadopago',
            'event_id' => $eventId,
            'event_type' => (string) $request->input('action', 'payment.updated'),
            'payload' => json_encode($request->all(), JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $checkout->reconcile($checkout->fetchPayment((int) $dataId));

        DB::table('payment_webhook_events')->where('event_id', $eventId)->update([
            'processed_at' => now(),
            'updated_at' => now(),
        ]);

        return response('ok');
    }
}
