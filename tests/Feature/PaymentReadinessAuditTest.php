<?php

namespace Tests\Feature;

use App\Jobs\FinalizeMarketplacePayment;
use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Post;
use App\Models\PostPromotion;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payments\MercadoPagoPromotionCheckout;
use App\Services\Payments\PaymentGatewayResolver;
use App\Services\Payments\StripeConnectGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use MercadoPago\Resources\Payment as MercadoPagoPayment;
use Stripe\StripeClient;
use Tests\TestCase;

/** Regression coverage for the production-readiness audit. */
class PaymentReadinessAuditTest extends TestCase
{
    use RefreshDatabase;

    private function payment(): Payment
    {
        Queue::fake();
        config(['services.stripe.webhook_secret' => 'whsec_audit_fixture']);
        $vendor = Vendor::create(['user_id' => User::factory()->create()->id, 'display_name' => 'Audit', 'slug' => 'audit', 'status' => 'active']);
        $order = Order::create(['public_id' => (string) Str::uuid(), 'buyer_id' => User::factory()->create()->id, 'vendor_id' => $vendor->id, 'status' => 'awaiting_payment', 'fulfillment_type' => 'service', 'subtotal_amount' => 10000, 'commission_amount' => 800, 'total_amount' => 10000, 'currency' => 'MXN']);

        return Payment::create(['order_id' => $order->id, 'provider' => 'stripe', 'provider_reference' => 'pi_audit_fixture', 'status' => 'pending', 'gross_amount' => 10000, 'commission_amount' => 800, 'vendor_net_amount' => 9200, 'currency' => 'MXN']);
    }

    private function event(string $id, string $type, int $amount = 10000, string $currency = 'mxn', bool $live = false, int $expected = 200): void
    {
        $body = json_encode(['id' => $id, 'object' => 'event', 'type' => $type, 'livemode' => $live, 'data' => ['object' => ['id' => 'pi_audit_fixture', 'object' => 'payment_intent', 'amount' => $amount, 'amount_received' => $amount, 'currency' => $currency, 'payment_method_types' => ['card']]]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_audit_fixture');
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature], $body)->assertStatus($expected);
    }

    public function test_second_success_event_must_not_refund_paid_order(): void
    {
        $payment = $this->payment();
        $this->event('evt_audit_1', 'payment_intent.succeeded');
        $this->event('evt_audit_2', 'payment_intent.succeeded');
        $this->assertSame('paid', $payment->fresh()->status->value);
    }

    public function test_delayed_failure_must_not_downgrade_paid_payment(): void
    {
        $payment = $this->payment();
        $this->event('evt_audit_1', 'payment_intent.succeeded');
        $this->event('evt_audit_2', 'payment_intent.payment_failed');
        $this->assertSame('paid', $payment->fresh()->status->value);
    }

    public function test_wrong_amount_must_not_fulfill_order(): void
    {
        $payment = $this->payment();
        $this->event('evt_audit_1', 'payment_intent.succeeded', 1);
        $this->assertSame('awaiting_payment', $payment->order->fresh()->status->value);
    }

    public function test_refunded_promotion_must_not_remain_active(): void
    {
        $user = User::factory()->create();
        $post = Post::create(['user_id' => $user->id, 'type' => 'service', 'body' => 'Audit fixture', 'published_at' => now()]);
        $promotion = PostPromotion::create(['post_id' => $post->id, 'user_id' => $user->id, 'status' => 'active', 'duration_days' => 7, 'amount' => 4900, 'currency' => 'MXN', 'payment_provider' => 'mercadopago', 'provider_payment_id' => '123']);
        $payment = new MercadoPagoPayment;
        $payment->id = 123;
        $payment->external_reference = 'promotion:'.$promotion->id;
        $payment->transaction_amount = 49.0;
        $payment->currency_id = 'MXN';
        $payment->status = 'refunded';
        $payment->live_mode = false;
        app(MercadoPagoPromotionCheckout::class)->reconcile($payment);
        $this->assertNotSame('active', $promotion->fresh()->status);
        $payment->status = 'approved';
        app(MercadoPagoPromotionCheckout::class)->reconcile($payment);
        $this->assertSame('refunded', $promotion->fresh()->status);
    }

    public function test_success_after_failed_attempt_can_confirm_the_same_intent(): void
    {
        $payment = $this->payment();
        $this->event('evt_fail', 'payment_intent.payment_failed');
        $this->event('evt_success', 'payment_intent.succeeded');
        $this->assertSame('paid', $payment->fresh()->status->value);
    }

    public function test_wrong_currency_and_environment_never_confirm_order(): void
    {
        $payment = $this->payment();
        $this->event('evt_currency', 'payment_intent.succeeded', 10000, 'usd');
        $this->event('evt_live', 'payment_intent.succeeded', 10000, 'mxn', true, 400);
        $this->assertSame('pending', $payment->fresh()->status->value);
    }

    public function test_late_payment_of_cancelled_order_queues_one_refund(): void
    {
        $payment = $this->payment();
        $payment->order->update(['status' => 'cancelled']);
        $payment->update(['status' => 'cancelled']);
        $this->event('evt_late', 'payment_intent.succeeded');
        $this->event('evt_late_duplicate', 'payment_intent.succeeded');
        $this->assertSame('refund_pending', $payment->fresh()->status->value);
        Queue::assertPushed(FinalizeMarketplacePayment::class, 1);
    }

    public function test_success_never_overwrites_released_or_refunded_payment(): void
    {
        $payment = $this->payment();
        foreach (['released', 'refunded', 'release_pending', 'refund_pending'] as $status) {
            $payment->update(['status' => $status]);
            $this->event('evt_'.$status, 'payment_intent.succeeded');
            $this->assertSame($status, $payment->fresh()->status->value);
        }
        Queue::assertNothingPushed();
    }

    public function test_job_uses_stored_provider_not_current_default(): void
    {
        $payment = $this->payment();
        $payment->update(['status' => 'refund_pending']);
        config(['marketplace.payment_driver' => 'fake']);
        $gateway = \Mockery::mock(StripeConnectGateway::class);
        $gateway->shouldReceive('refundPayment')->once()->with(\Mockery::on(fn ($value) => $value->id === $payment->id));
        $this->app->instance(StripeConnectGateway::class, $gateway);
        (new FinalizeMarketplacePayment($payment->id, 'refund'))->handle(app(PaymentGatewayResolver::class));
    }

    public function test_pending_refund_is_completed_by_signed_notification(): void
    {
        $payment = $this->payment();
        $payment->update(['status' => 'refund_pending', 'provider_payload' => ['refund_id' => 're_fixture']]);
        $body = json_encode(['id' => 'evt_refund_fixture', 'object' => 'event', 'livemode' => false, 'type' => 'refund.updated', 'data' => ['object' => ['id' => 're_fixture', 'object' => 'refund', 'payment_intent' => 'pi_audit_fixture', 'amount' => 10000, 'currency' => 'mxn', 'status' => 'succeeded']]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_audit_fixture');
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature], $body)->assertOk();
        $this->assertSame('refunded', $payment->fresh()->status->value);
        $this->assertNotNull($payment->fresh()->refunded_at);
    }

    public function test_registration_is_rate_limited_without_creating_accounts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/register', [])->assertUnprocessable();
        }
        $this->postJson('/register', [])->assertTooManyRequests();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_unassociated_webhook_is_retriable_and_not_marked_processed(): void
    {
        $payment = $this->payment();
        $payment->delete();
        $this->event('evt_early', 'payment_intent.succeeded', expected: 503);
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_gateway_rejects_live_key_when_test_mode_is_expected(): void
    {
        config(['services.stripe.livemode' => false]);
        $gateway = new StripeConnectGateway(new StripeClient('sk_live_fixture_not_a_real_key'));
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El entorno Stripe configurado no coincide');
        $gateway->createPayment(new Order);
    }

    public function test_admin_must_not_resolve_own_linked_client_dispute(): void
    {
        $payment = $this->payment();
        $order = $payment->order;
        $order->update(['status' => 'disputed']);
        $payment->update(['status' => 'paid']);
        $buyer = $order->buyer;
        $admin = AdminUser::factory()->create(['email' => $buyer->email, 'phone' => $buyer->phone]);
        AccountIdentityLink::create(['admin_user_id' => $admin->id, 'user_id' => $buyer->id, 'email' => $buyer->email, 'phone' => $buyer->phone, 'approved_at' => now()]);
        $dispute = Dispute::create(['public_id' => (string) Str::uuid(), 'order_id' => $order->id, 'opened_by' => $buyer->id, 'reason' => 'poor_service', 'status' => 'open', 'description' => 'Audit fixture dispute for linked identity.', 'order_status_before' => 'in_progress']);
        $response = $this->actingAs($admin, 'admin')->patch(route('admin.disputes.resolve', $dispute), ['outcome' => 'cancel', 'resolution' => 'Audit: resolving own linked client dispute.']);
        $this->assertSame('open', $dispute->fresh()->status->value, 'A linked administrator must leave their own dispute unresolved.');
        $response->assertForbidden();
    }
}
