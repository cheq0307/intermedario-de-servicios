<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\PostPromotion;
use App\Models\User;
use App\Services\Payments\MercadoPagoPromotionCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MercadoPago\Resources\Payment as MercadoPagoPayment;
use Tests\TestCase;

class MercadoPagoPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_resume_an_existing_checkout_without_creating_another_preference(): void
    {
        $user = User::factory()->create();
        $promotion = $this->promotionFor($user, [
            'payment_provider' => 'mercadopago',
            'provider_preference_id' => 'pref_test_1',
            'checkout_url' => 'https://www.mercadopago.com.mx/checkout/v1/redirect?pref_id=pref_test_1',
        ]);

        $this->actingAs($user)
            ->post(route('promotions.checkout', $promotion))
            ->assertRedirect($promotion->checkout_url);
    }

    public function test_another_user_cannot_pay_someone_elses_promotion(): void
    {
        $owner = User::factory()->create();
        $promotion = $this->promotionFor($owner);

        $this->actingAs(User::factory()->create())
            ->post(route('promotions.checkout', $promotion))
            ->assertForbidden();
    }

    public function test_approved_payment_activates_promotion_only_when_amount_and_currency_match(): void
    {
        $user = User::factory()->create();
        $promotion = $this->promotionFor($user);
        $payment = new MercadoPagoPayment;
        $payment->id = 987654321;
        $payment->external_reference = 'promotion:'.$promotion->id;
        $payment->transaction_amount = 49.00;
        $payment->currency_id = 'MXN';
        $payment->status = 'approved';
        $payment->status_detail = 'accredited';
        $payment->payment_method_id = 'visa';
        $payment->payment_type_id = 'credit_card';
        $payment->live_mode = false;
        $payment->date_approved = now()->toIso8601String();
        $payment->date_last_updated = now()->toIso8601String();

        app(MercadoPagoPromotionCheckout::class)->reconcile($payment);

        $promotion->refresh();
        $this->assertSame('active', $promotion->status);
        $this->assertSame('mercadopago', $promotion->payment_provider);
        $this->assertSame('987654321', $promotion->provider_payment_id);
        $this->assertNotNull($promotion->paid_at);
        $this->assertNotNull($promotion->ends_at);
    }

    public function test_unsigned_webhook_is_rejected(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secret_test']);

        $this->postJson(route('mercadopago.webhook'), [
            'id' => 'notification-1',
            'type' => 'payment',
            'data' => ['id' => '123'],
        ])->assertUnauthorized();
    }

    public function test_valid_non_payment_webhook_is_acknowledged_without_api_call(): void
    {
        $secret = 'secret_test';
        $requestId = 'request-test-1';
        $dataId = '123';
        $timestamp = (string) ((int) (microtime(true) * 1000));
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $signature = hash_hmac('sha256', $manifest, $secret);
        config(['services.mercadopago.webhook_secret' => $secret]);

        $this->withHeaders([
            'x-request-id' => $requestId,
            'x-signature' => "ts={$timestamp},v1={$signature}",
        ])->postJson(route('mercadopago.webhook').'?data.id='.$dataId, [
            'id' => 'notification-2',
            'type' => 'test',
            'data' => ['id' => $dataId],
        ])->assertOk();

        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    private function promotionFor(User $user, array $attributes = []): PostPromotion
    {
        $post = Post::create([
            'user_id' => $user->id,
            'type' => 'service',
            'body' => 'Servicio a promocionar',
            'published_at' => now(),
        ]);

        return PostPromotion::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'status' => 'pending_payment',
            'duration_days' => 7,
            'amount' => 4900,
            'currency' => 'MXN',
            ...$attributes,
        ]);
    }
}
