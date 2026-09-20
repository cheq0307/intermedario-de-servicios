<?php

namespace App\Services\Payments;

use App\Models\JobVacancy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use MercadoPago\Client\Common\RequestOptions;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Resources\Payment;

class MercadoPagoVacancyCheckout
{
    public function checkout(JobVacancy $vacancy): string
    {
        return Cache::lock('vacancy-checkout:'.$vacancy->id, 60)->block(5, function () use ($vacancy): string {
            $vacancy->refresh();
            abort_unless($vacancy->status === 'pending_payment', 422);
            if ($vacancy->checkout_url) {
                return $vacancy->checkout_url;
            }
            abort_unless(config('services.mercadopago.access_token'), 503, 'El cobro de vacantes todavía no está configurado.');
            MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
            $options = new RequestOptions;
            $options->setCustomHeaders(['X-Idempotency-Key: vacancy-'.$vacancy->public_id]);
            $preference = (new PreferenceClient)->create([
                'items' => [['id' => 'vacancy-'.$vacancy->id, 'title' => 'Publicación de vacante en Plaza Local', 'quantity' => 1, 'currency_id' => $vacancy->currency, 'unit_price' => round($vacancy->publication_fee_amount / 100, 2)]],
                'external_reference' => 'vacancy:'.$vacancy->id,
                'payer' => ['email' => $vacancy->employer->email],
                'back_urls' => array_fill_keys(['success', 'pending', 'failure'], route('vacancies.payment-return', $vacancy)),
                'auto_return' => 'approved',
                'notification_url' => route('mercadopago.webhook'),
            ], $options);
            $url = config('services.mercadopago.sandbox') ? $preference->sandbox_init_point : $preference->init_point;
            throw_unless($preference->id && $url, new \RuntimeException('No se recibió un enlace de pago.'));
            $vacancy->forceFill(['payment_provider' => 'mercadopago', 'provider_preference_id' => $preference->id, 'checkout_url' => $url])->save();

            return $url;
        });
    }

    public function reconcile(Payment $payment): ?JobVacancy
    {
        if (! preg_match('/^vacancy:(\d+)$/', (string) $payment->external_reference, $matches)) {
            return null;
        }

        return DB::transaction(function () use ($payment, $matches): ?JobVacancy {
            $vacancy = JobVacancy::lockForUpdate()->find((int) $matches[1]);
            if (! $vacancy) {
                return null;
            }
            if (($payment->live_mode ?? null) !== ! (bool) config('services.mercadopago.sandbox')
                || (int) round($payment->transaction_amount * 100) !== $vacancy->publication_fee_amount
                || strtoupper((string) $payment->currency_id) !== $vacancy->currency) {
                report(new \RuntimeException('Pago de vacante con entorno, importe o moneda incorrectos.'));

                return $vacancy;
            }
            if ($vacancy->paid_at && $vacancy->payment_reference !== (string) $payment->id) {
                report(new \RuntimeException('Segundo pago para una vacante ya pagada; requiere conciliación.'));

                return $vacancy;
            }
            if (in_array($vacancy->status, ['refunded', 'charged_back'], true)) {
                return $vacancy;
            }
            $data = ['payment_provider' => 'mercadopago', 'payment_payload' => ['status' => $payment->status, 'payment_id' => (string) $payment->id]];
            if (in_array($payment->status, ['refunded', 'charged_back'], true)) {
                $data += ['status' => $payment->status, 'closed_at' => now()];
            } elseif ($payment->status === 'approved' && ! $vacancy->paid_at) {
                // Never reopen an offer deliberately closed while payment was pending.
                if ($vacancy->status !== 'pending_payment') {
                    report(new \RuntimeException('Pago tardío de vacante cerrada; requiere conciliación.'));
                    $vacancy->forceFill($data)->save();

                    return $vacancy;
                }
                $data += ['status' => 'published', 'payment_reference' => (string) $payment->id, 'paid_at' => now(), 'published_at' => now(), 'expires_at' => now()->addDays(config('marketplace.job_posting_days'))];
            }
            $vacancy->forceFill($data)->save();

            return $vacancy->fresh();
        });
    }
}
