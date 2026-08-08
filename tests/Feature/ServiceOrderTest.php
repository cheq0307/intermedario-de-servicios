<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\JobProposal;
use App\Models\JobRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ServiceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_and_client_follow_the_service_order_lifecycle(): void
    {
        [$client, $provider, $order, $jobRequest] = $this->scenario();
        $this->actingAs($provider)->get(route('orders.show', $order))->assertOk();

        $this->actingAs($provider)->patch(route('orders.start', $order))->assertStatus(422);
        $this->actingAs($client)->post(route('orders.simulate-payment', $order))->assertRedirect();
        $this->assertSame('paid', $order->fresh()->status->value);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'paid',
        ]);

        $this->actingAs($provider)->patch(route('orders.start', $order))->assertRedirect();
        $this->assertSame('in_progress', $order->fresh()->status->value);
        $this->assertNotNull($order->fresh()->started_at);
        $this->assertDatabaseHas('messages', ['sender_id' => $provider->id, 'type' => 'system']);

        $this->actingAs($client)->patch(route('orders.deliver', $order))->assertForbidden();
        $this->actingAs($provider)->patch(route('orders.deliver', $order))->assertRedirect();
        $this->assertSame('delivered', $order->fresh()->status->value);

        $this->actingAs($provider)->patch(route('orders.complete', $order))->assertForbidden();
        $this->actingAs($client)->patch(route('orders.complete', $order))->assertRedirect();
        $this->assertSame('completed', $order->fresh()->status->value);
        $this->assertSame('completed', $jobRequest->fresh()->status->value);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);
        $this->actingAs($client)->get(route('orders.show', $order))->assertOk()->assertSee('Califica esta experiencia');
    }

    public function test_participant_can_cancel_only_before_work_starts_and_reason_is_recorded(): void
    {
        [$client, $provider, $order, $jobRequest] = $this->scenario();

        $this->actingAs($client)->patch(route('orders.cancel', $order), [
            'reason' => 'El trabajo ya no será necesario por un cambio de planes.',
        ])->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status->value);
        $this->assertSame('cancelled', $jobRequest->fresh()->status->value);
        $this->assertNotNull($order->fresh()->cancellation_reason);

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'cancelled',
        ]);

        [$secondClient, $secondProvider, $secondOrder] = $this->scenario('segunda');
        $this->actingAs($secondClient)->post(route('orders.simulate-payment', $secondOrder));
        $this->actingAs($secondProvider)->patch(route('orders.start', $secondOrder));
        $this->actingAs($secondProvider)->patch(route('orders.cancel', $secondOrder), [
            'reason' => 'Intento de cancelar un trabajo que ya está iniciado.',
        ])->assertStatus(422);
    }

    public function test_outsider_cannot_see_or_change_an_order(): void
    {
        [, , $order] = $this->scenario();
        $outsider = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($outsider)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($outsider)->patch(route('orders.start', $order))->assertForbidden();
        $this->actingAs($outsider)->patch(route('orders.cancel', $order), [
            'reason' => 'No debería poder modificar una orden de terceros.',
        ])->assertForbidden();
    }

    public function test_order_index_contains_only_participant_orders(): void
    {
        [$client, , $order] = $this->scenario();
        $outsider = User::factory()->create(['account_type' => 'client']);

        $this->actingAs($client)->get(route('orders.index'))->assertOk()->assertSee($order->jobRequest->title);
        $this->actingAs($outsider)->get(route('orders.index'))->assertOk()->assertDontSee($order->jobRequest->title);
    }

    private function scenario(string $suffix = 'principal'): array
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Servicios '.$suffix,
            'slug' => 'servicios-'.$suffix.'-'.Str::lower(Str::random(6)),
            'status' => 'active',
            'commission_rate_basis_points' => 800,
        ]);
        $jobRequest = JobRequest::create([
            'public_id' => (string) Str::uuid(),
            'client_id' => $client->id,
            'title' => 'Reparación de fuga '.$suffix,
            'description' => 'Se necesita reparar una fuga y revisar la instalación.',
            'status' => 'assigned',
            'urgency' => 'soon',
            'published_at' => now(),
        ]);
        $proposal = JobProposal::create([
            'public_id' => (string) Str::uuid(),
            'job_request_id' => $jobRequest->id,
            'provider_id' => $provider->id,
            'amount' => 125000,
            'currency' => 'MXN',
            'message' => 'Incluye diagnóstico, materiales básicos y mano de obra.',
            'estimated_days' => 2,
            'status' => 'accepted',
            'responded_at' => now(),
        ]);
        $order = Order::create([
            'public_id' => (string) Str::uuid(),
            'buyer_id' => $client->id,
            'vendor_id' => $vendor->id,
            'job_request_id' => $jobRequest->id,
            'job_proposal_id' => $proposal->id,
            'status' => 'awaiting_payment',
            'fulfillment_type' => 'service',
            'subtotal_amount' => 125000,
            'commission_amount' => 10000,
            'total_amount' => 125000,
            'currency' => 'MXN',
            'accepted_at' => now(),
            'due_at' => now()->addDays(2),
        ]);
        $order->items()->create([
            'name_snapshot' => $jobRequest->title,
            'quantity' => 1,
            'unit_price_amount' => 125000,
            'line_total_amount' => 125000,
        ]);
        $participantIds = collect([$client->id, $provider->id])->sort()->values();
        $order->payments()->create([
            'provider' => 'fake',
            'provider_reference' => 'fake_'.Str::uuid(),
            'status' => 'pending',
            'gross_amount' => 125000,
            'commission_amount' => 10000,
            'vendor_net_amount' => 115000,
            'currency' => 'MXN',
            'provider_payload' => [],
        ]);
        $conversation = Conversation::create([
            'public_id' => (string) Str::uuid(),
            'direct_key' => $participantIds->implode(':'),
        ]);
        $conversation->participants()->attach($participantIds->all());

        return [$client, $provider, $order, $jobRequest];
    }
}
