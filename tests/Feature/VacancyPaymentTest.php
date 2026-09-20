<?php

namespace Tests\Feature;

use App\Models\Community;
use App\Models\JobVacancy;
use App\Models\User;
use App\Services\Payments\MercadoPagoPromotionCheckout;
use App\Services\Payments\MercadoPagoVacancyCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPHttpClient;
use MercadoPago\Net\MPResponse;
use MercadoPago\Resources\Payment;
use Tests\TestCase;

class VacancyPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function vacancy(): JobVacancy
    {
        config(['marketplace.vacancy_payment_driver' => 'mercadopago', 'services.mercadopago.sandbox' => true]);
        $community = Community::create(['name' => 'Audit', 'municipality' => 'Audit', 'state' => 'Puebla', 'is_active' => true]);

        return JobVacancy::create(['public_id' => fake()->uuid(), 'employer_id' => User::factory()->create()->id, 'community_id' => $community->id, 'title' => 'Ayudante de cocina', 'description' => 'Vacante de prueba para cobros seguros.', 'pay_period' => 'weekly', 'work_mode' => 'onsite', 'contract_type' => 'temporary', 'vacancies_count' => 1, 'publication_fee_amount' => 9900, 'currency' => 'MXN', 'status' => 'pending_payment']);
    }

    private function payment(JobVacancy $vacancy, string $status = 'approved'): Payment
    {
        $payment = new Payment;
        $payment->id = 123;
        $payment->external_reference = 'vacancy:'.$vacancy->id;
        $payment->transaction_amount = 99.0;
        $payment->currency_id = 'MXN';
        $payment->live_mode = false;
        $payment->status = $status;

        return $payment;
    }

    public function test_owner_can_resume_checkout_without_publishing_and_outsider_cannot_pay(): void
    {
        $vacancy = $this->vacancy();
        $vacancy->forceFill(['checkout_url' => 'https://www.mercadopago.com.mx/checkout/test'])->save();
        $this->actingAs($vacancy->employer)->post(route('vacancies.pay', $vacancy))->assertRedirect($vacancy->checkout_url);
        $this->assertSame('pending_payment', $vacancy->fresh()->status);
        $this->actingAs(User::factory()->create())->post(route('vacancies.pay', $vacancy))->assertForbidden();
    }

    public function test_checkout_creates_preference_for_frozen_fee_and_reuses_it(): void
    {
        $vacancy = $this->vacancy();
        config(['services.mercadopago.access_token' => 'TEST-fixture-not-a-real-key']);
        $original = MercadoPagoConfig::getHttpClient();
        $transport = \Mockery::mock(MPHttpClient::class);
        $transport->shouldReceive('send')->once()->with(\Mockery::on(function ($request) use ($vacancy) {
            $data = json_decode($request->getPayload(), true);

            return $request->getUri() === '/checkout/preferences'
                && $data['items'][0]['unit_price'] === 99
                && $data['external_reference'] === 'vacancy:'.$vacancy->id
                && $data['notification_url'] === route('mercadopago.webhook');
        }))->andReturn(new MPResponse(201, ['id' => 'pref_fixture', 'sandbox_init_point' => 'https://sandbox.mercadopago.com.mx/checkout/fixture']));
        MercadoPagoConfig::setHttpClient($transport);
        try {
            $checkout = app(MercadoPagoVacancyCheckout::class);
            $url = $checkout->checkout($vacancy);
            $this->assertSame($url, $checkout->checkout($vacancy));
            $this->assertSame('pref_fixture', $vacancy->fresh()->provider_preference_id);
            $this->assertNull($vacancy->fresh()->paid_at);
        } finally {
            MercadoPagoConfig::setHttpClient($original);
        }
    }

    public function test_return_url_cannot_fake_approval(): void
    {
        $vacancy = $this->vacancy();
        $this->actingAs($vacancy->employer)->get(route('vacancies.payment-return', $vacancy).'?status=approved&payment_id=123')->assertRedirect();
        $this->assertNull($vacancy->fresh()->paid_at);
    }

    public function test_approval_is_idempotent_and_refund_cannot_be_undone_by_stale_approval(): void
    {
        $vacancy = $this->vacancy();
        $checkout = app(MercadoPagoVacancyCheckout::class);
        $checkout->reconcile($this->payment($vacancy));
        $end = $vacancy->fresh()->expires_at;
        $this->travel(1)->days();
        $checkout->reconcile($this->payment($vacancy));
        $this->assertTrue($end->equalTo($vacancy->fresh()->expires_at));
        $this->assertSame('published', $vacancy->fresh()->status);
        $checkout->reconcile($this->payment($vacancy, 'refunded'));
        $checkout->reconcile($this->payment($vacancy));
        $this->assertSame('refunded', $vacancy->fresh()->status);
    }

    public function test_invalid_amount_currency_or_mode_never_publishes(): void
    {
        $vacancy = $this->vacancy();
        foreach (['transaction_amount' => 1.0, 'currency_id' => 'USD', 'live_mode' => true] as $field => $value) {
            $payment = $this->payment($vacancy);
            $payment->{$field} = $value;
            app(MercadoPagoVacancyCheckout::class)->reconcile($payment);
            $this->assertSame('pending_payment', $vacancy->fresh()->status);
        }
    }

    public function test_closed_vacancy_is_not_reopened_by_late_approval(): void
    {
        $vacancy = $this->vacancy();
        $vacancy->update(['status' => 'closed']);
        app(MercadoPagoVacancyCheckout::class)->reconcile($this->payment($vacancy));
        $this->assertSame('closed', $vacancy->fresh()->status);
    }

    public function test_signed_webhook_routes_vacancy_payment_to_reconciliation(): void
    {
        $vacancy = $this->vacancy();
        $payment = $this->payment($vacancy);
        $this->mock(MercadoPagoPromotionCheckout::class, function ($mock) use ($payment) {
            $mock->shouldReceive('fetchPayment')->once()->with(123)->andReturn($payment);
        });
        config(['services.mercadopago.webhook_secret' => 'audit_secret']);
        $timestamp = (string) ((int) (microtime(true) * 1000));
        $signature = hash_hmac('sha256', "id:123;request-id:audit;ts:{$timestamp};", 'audit_secret');
        $this->withHeaders(['x-signature' => "ts={$timestamp},v1={$signature}", 'x-request-id' => 'audit'])->postJson(route('mercadopago.webhook'), ['id' => 'audit_notification', 'type' => 'payment', 'data' => ['id' => '123']])->assertOk();
        $this->assertSame('published', $vacancy->fresh()->status);
    }
}
