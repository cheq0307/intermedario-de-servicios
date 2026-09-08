<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Dispute;
use App\Models\JobRequest;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\TestCase;

class DisputeAndReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_opens_dispute_and_both_parties_can_reply(): void
    {
        [$client, $provider, $order] = $this->scenario('in_progress');

        $this->actingAs($client, 'web')->post(route('disputes.store', $order), [
            'reason' => 'poor_service',
            'description' => 'El trabajo presenta problemas y necesito que la plataforma revise lo ocurrido.',
        ])->assertRedirect();

        $dispute = Dispute::firstOrFail();
        $this->assertSame('disputed', $order->fresh()->status->value);
        $this->assertSame('in_progress', $dispute->order_status_before);
        $this->actingAs($provider, 'web')->get(route('disputes.show', $dispute))->assertOk();
        $this->actingAs($provider, 'web')->post(route('disputes.reply', $dispute), [
            'body' => 'Agrego mi explicación de lo sucedido durante el servicio.',
        ])->assertRedirect();
        $this->assertDatabaseCount('dispute_messages', 2);
    }

    public function test_outsider_cannot_access_dispute_and_non_admin_cannot_resolve_it(): void
    {
        [$client, , $order] = $this->scenario('delivered');
        $this->actingAs($client, 'web')->post(route('disputes.store', $order), [
            'reason' => 'different_work',
            'description' => 'La entrega no coincide con las condiciones que se acordaron originalmente.',
        ]);
        $dispute = Dispute::firstOrFail();
        $outsider = User::factory()->create();

        $this->actingAs($outsider, 'web')->get(route('disputes.show', $dispute))->assertForbidden();
        $this->actingAs($client, 'web')->patch(route('disputes.resolve', $dispute), [
            'outcome' => 'cancel',
            'resolution' => 'Esta resolución no debería ser autorizada por una de las partes.',
        ])->assertForbidden();
    }

    public function test_admin_can_resolve_dispute_and_restore_previous_order_status(): void
    {
        [$client, , $order] = $this->scenario('delivered');
        $this->actingAs($client, 'web')->post(route('disputes.store', $order), [
            'reason' => 'other',
            'description' => 'Existe una diferencia que necesita revisión antes de confirmar la entrega.',
        ]);
        $dispute = Dispute::firstOrFail();
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin')->get(route('disputes.admin-index'))->assertOk()->assertSee('Servicio sujeto a seguimiento');

        $this->actingAs($admin, 'admin')->patch(route('admin.disputes.resolve', $dispute), [
            'outcome' => 'resume',
            'resolution' => 'Se revisó la información y ambas partes pueden continuar desde la entrega.',
        ])->assertRedirect();

        $this->assertSame('resolved', $dispute->fresh()->status->value);
        $this->assertSame('delivered', $order->fresh()->status->value);
        $this->assertSame($admin->id, $dispute->fresh()->admin_user_id);
    }

    public function test_admin_resolution_updates_held_fake_payments(): void
    {
        [$client, , $order] = $this->scenario('delivered');
        $this->actingAs($client, 'web')->post(route('disputes.store', $order), [
            'reason' => 'poor_service',
            'description' => 'El servicio necesita revisiÃ³n antes de liberar el pago retenido al proveedor.',
        ]);
        $dispute = Dispute::latest('id')->firstOrFail();
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin')->patch(route('admin.disputes.resolve', $dispute), [
            'outcome' => 'complete',
            'resolution' => 'La evidencia confirma la entrega y corresponde liberar el pago retenido.',
        ])->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'released',
        ]);

        [$secondClient, , $secondOrder] = $this->scenario('in_progress');
        $this->actingAs($secondClient, 'web')->post(route('disputes.store', $secondOrder), [
            'reason' => 'no_show',
            'description' => 'El proveedor no se presentÃ³ y corresponde revisar la devoluciÃ³n del pago.',
        ]);
        $secondDispute = Dispute::latest('id')->firstOrFail();
        $this->actingAs($admin, 'admin')->patch(route('admin.disputes.resolve', $secondDispute), [
            'outcome' => 'cancel',
            'resolution' => 'La evidencia confirma la ausencia y corresponde devolver el pago al cliente.',
        ])->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'order_id' => $secondOrder->id,
            'status' => 'refunded',
        ]);
    }

    public function test_only_participants_can_review_completed_orders_once(): void
    {
        [$client, $provider, $order] = $this->scenario('completed');
        $outsider = User::factory()->create();

        $this->actingAs($client, 'web')->post(route('reviews.store', $order), ['rating' => 5, 'comment' => 'Trabajo excelente y puntual.'])->assertRedirect();
        $this->actingAs($provider, 'web')->post(route('reviews.store', $order), ['rating' => 4, 'comment' => 'Cliente claro y respetuoso.'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['author_id' => $client->id, 'subject_user_id' => $provider->id, 'rating' => 5]);
        $this->assertDatabaseHas('reviews', ['author_id' => $provider->id, 'subject_user_id' => $client->id, 'rating' => 4]);
        $review = Review::where('author_id', $client->id)->firstOrFail();
        $this->actingAs($client, 'web')->patch(route('reviews.update', $review), ['rating' => 4, 'comment' => 'Actualizo mi opinión después de revisar el resultado.'])->assertRedirect();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 4, 'comment' => 'Actualizo mi opinión después de revisar el resultado.']);
        $this->actingAs($outsider, 'web')->patch(route('reviews.update', $review), ['rating' => 1])->assertForbidden();
        $this->actingAs($client, 'web')->post(route('reviews.store', $order), ['rating' => 1])->assertStatus(422);
        $this->actingAs($outsider, 'web')->post(route('reviews.store', $order), ['rating' => 5])->assertForbidden();
    }

    public function test_order_cannot_be_reviewed_before_completion(): void
    {
        [$client, , $order] = $this->scenario('delivered');
        $this->actingAs($client, 'web')->post(route('reviews.store', $order), ['rating' => 5])->assertStatus(422);
    }

    public function test_legacy_role_promotion_command_is_not_exposed(): void
    {
        $this->assertArrayNotHasKey('plaza:grant-admin', Artisan::all());
    }

    public function test_bootstrap_refuses_to_create_a_second_owner(): void
    {
        AdminUser::factory()->superadmin()->create();
        $this->artisan('plaza:create-superadmin')->assertFailed();
        $this->assertDatabaseCount('admin_users', 1);
    }

    private function scenario(string $status): array
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);
        $vendor = Vendor::create([
            'user_id' => $provider->id,
            'display_name' => 'Proveedor para flujo',
            'slug' => 'proveedor-flujo-'.Str::lower(Str::random(6)),
            'status' => 'active',
        ]);
        $jobRequest = JobRequest::create([
            'public_id' => (string) Str::uuid(),
            'client_id' => $client->id,
            'title' => 'Servicio sujeto a seguimiento',
            'description' => 'Descripción suficiente del trabajo contratado.',
            'status' => $status === 'completed' ? 'completed' : 'assigned',
            'urgency' => 'normal',
            'published_at' => now(),
        ]);
        $order = Order::create([
            'public_id' => (string) Str::uuid(),
            'buyer_id' => $client->id,
            'vendor_id' => $vendor->id,
            'job_request_id' => $jobRequest->id,
            'status' => $status,
            'fulfillment_type' => 'service',
            'subtotal_amount' => 100000,
            'commission_amount' => 8000,
            'total_amount' => 100000,
            'currency' => 'MXN',
            'accepted_at' => now(),
            'started_at' => now(),
            'delivered_at' => in_array($status, ['delivered', 'completed'], true) ? now() : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        $order->payments()->create([
            'provider' => 'fake',
            'provider_reference' => 'fake_'.Str::uuid(),
            'status' => $status === 'completed' ? 'released' : 'paid',
            'gross_amount' => 100000,
            'commission_amount' => 8000,
            'vendor_net_amount' => 92000,
            'currency' => 'MXN',
            'paid_at' => now(),
            'released_at' => $status === 'completed' ? now() : null,
            'provider_payload' => [],
        ]);

        return [$client, $provider, $order];
    }
}
