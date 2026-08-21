<?php

namespace App\Services\Payments;

use App\Models\PostPromotion;
use Illuminate\Support\Facades\DB;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment as MercadoPagoPayment;

class MercadoPagoPromotionCheckout
{
    private function configure(): void
    {
        $token = (string) config('services.mercadopago.access_token');
        throw_unless($token !== '', new \RuntimeException('Falta MERCADOPAGO_ACCESS_TOKEN.'));

        MercadoPagoConfig::setAccessToken($token);
    }

    /** @return array{preference_id: string, checkout_url: string, payload: array<string, mixed>} */
    public function create(PostPromotion $promotion): array
    {
        $this->configure();
        $promotion->loadMissing(['post.listing', 'user']);
        $options = new RequestOptions;
        $options->setCustomHeaders(['X-Idempotency-Key' => 'post-promotion-'.$promotion->id]);

        $preference = (new PreferenceClient)->create([
            'items' => [[
                'id' => 'promotion-'.$promotion->id,
                'title' => 'Promoción en Plaza Local · '.$promotion->duration_days.' días',
                'description' => $promotion->post->listing?->name ?: 'Publicación patrocinada',
                'currency_id' => $promotion->currency,
                'quantity' => 1,
                'unit_price' => round($promotion->amount / 100, 2),
            ]],
            'payer' => ['email' => $promotion->user->email],
            'back_urls' => [
                'success' => route('promotions.return', ['promotion' => $promotion, 'result' => 'success']),
                'pending' => route('promotions.return', ['promotion' => $promotion, 'result' => 'pending']),
                'failure' => route('promotions.return', ['promotion' => $promotion, 'result' => 'failure']),
            ],
            'auto_return' => 'approved',
            'external_reference' => 'promotion:'.$promotion->id,
            'metadata' => [
                'purpose' => 'post_promotion',
                'promotion_id' => $promotion->id,
                'user_id' => $promotion->user_id,
            ],
            'notification_url' => route('mercadopago.webhook'),
            'statement_descriptor' => 'PLAZA LOCAL',
        ], $options);

        $checkoutUrl = config('services.mercadopago.sandbox')
            ? $preference->sandbox_init_point
            : $preference->init_point;

        throw_unless($preference->id && $checkoutUrl, new \RuntimeException('Mercado Pago no devolvió una URL de pago.'));

        return [
            'preference_id' => $preference->id,
            'checkout_url' => $checkoutUrl,
            'payload' => ['live_mode' => $preference->live_mode, 'created_at' => $preference->date_created],
        ];
    }

    public function fetchPayment(int $paymentId): MercadoPagoPayment
    {
        $this->configure();
        return (new PaymentClient)->get($paymentId);
    }

    public function reconcile(MercadoPagoPayment $payment): ?PostPromotion
    {
        if (! preg_match('/^promotion:(\d+)$/', (string) $payment->external_reference, $matches)) {
            return null;
        }

        return DB::transaction(function () use ($payment, $matches): ?PostPromotion {
            $promotion = PostPromotion::lockForUpdate()->find((int) $matches[1]);
            if (! $promotion) {
                return null;
            }

            if ($promotion->status === 'active' && $promotion->provider_payment_id !== null) {
                if ($promotion->provider_payment_id !== (string) $payment->id) {
                    report(new \RuntimeException('Se recibió un segundo pago para una promoción ya activada.'));
                }

                return $promotion;
            }

            $amountInCents = (int) round(((float) $payment->transaction_amount) * 100);
            if ($amountInCents !== $promotion->amount || strtoupper((string) $payment->currency_id) !== $promotion->currency) {
                report(new \RuntimeException('Pago de promoción con importe o moneda inesperados.'));

                return $promotion;
            }

            $payload = [
                'status' => $payment->status,
                'status_detail' => $payment->status_detail,
                'payment_method_id' => $payment->payment_method_id,
                'payment_type_id' => $payment->payment_type_id,
                'live_mode' => $payment->live_mode,
                'date_last_updated' => $payment->date_last_updated,
            ];

            $attributes = [
                'payment_provider' => 'mercadopago',
                'provider_payment_id' => (string) $payment->id,
                'payment_payload' => $payload,
            ];

            if ($payment->status === 'approved' && $promotion->status !== 'active') {
                $attributes += [
                    'status' => 'active',
                    'paid_at' => $payment->date_approved ?: now(),
                    'starts_at' => now(),
                    'ends_at' => now()->addDays($promotion->duration_days),
                ];
            } elseif (in_array($payment->status, ['rejected', 'cancelled'], true) && $promotion->status !== 'active') {
                $attributes['status'] = 'payment_failed';
            }

            $promotion->update($attributes);

            return $promotion->fresh();
        });
    }
}
