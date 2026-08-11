<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_payment_webhook_is_idempotent_and_marks_order_paid(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $buyer = User::factory()->create();
        $provider = User::factory()->create();
        $vendor = Vendor::create(['user_id' => $provider->id, 'display_name' => 'Proveedor', 'slug' => 'proveedor', 'status' => 'active']);
        $order = Order::create(['public_id' => (string) Str::uuid(), 'buyer_id' => $buyer->id, 'vendor_id' => $vendor->id, 'status' => 'awaiting_payment', 'fulfillment_type' => 'service', 'subtotal_amount' => 10000, 'commission_amount' => 800, 'total_amount' => 10000, 'currency' => 'MXN']);
        Payment::create(['order_id' => $order->id, 'provider' => 'stripe', 'provider_reference' => 'pi_test_123', 'status' => 'pending', 'gross_amount' => 10000, 'commission_amount' => 800, 'vendor_net_amount' => 9200, 'currency' => 'MXN']);
        $payload = json_encode(['id' => 'evt_test_123', 'object' => 'event', 'type' => 'payment_intent.succeeded', 'data' => ['object' => ['id' => 'pi_test_123', 'object' => 'payment_intent', 'payment_method_types' => ['card']]]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature], $payload)->assertOk();
        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature], $payload)->assertOk();

        $this->assertDatabaseHas('payments', ['provider_reference' => 'pi_test_123', 'status' => 'paid', 'method' => 'card']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_unsigned_webhook_is_rejected(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $this->postJson(route('stripe.webhook'), ['id' => 'evt_bad'])->assertBadRequest();
    }
}
