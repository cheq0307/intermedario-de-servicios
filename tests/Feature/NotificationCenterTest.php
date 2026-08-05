<?php

namespace Tests\Feature;

use App\Models\JobRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_open_own_notification(): void
    {
        $user = User::factory()->create();
        $user->notify(new MarketplaceActivity('Pedido listo', 'Ya puedes recogerlo.', 'dashboard'));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->get(route('notifications.index'))->assertOk()->assertSee('Pedido listo');
        $this->actingAs($user)->patch(route('notifications.open', $notification->id))->assertRedirect(route('dashboard'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $owner->notify(new MarketplaceActivity('Privada', 'Solo para el dueño.', 'dashboard'));

        $this->actingAs($outsider)->patch(route('notifications.open', $owner->notifications()->firstOrFail()->id))->assertNotFound();
    }

    public function test_client_is_notified_when_provider_submits_proposal(): void
    {
        $client = User::factory()->create(['account_type' => 'client']);
        $provider = User::factory()->create(['account_type' => 'provider']);
        Vendor::create(['user_id' => $provider->id, 'display_name' => 'Proveedor', 'slug' => 'proveedor', 'status' => 'active']);
        $job = JobRequest::create(['public_id' => (string) Str::uuid(), 'client_id' => $client->id, 'title' => 'Reparar una fuga', 'description' => 'Necesito reparar una fuga de agua.', 'status' => 'published', 'urgency' => 'soon', 'published_at' => now()]);

        $this->actingAs($provider)->post(route('job-proposals.store', $job), ['amount' => 500, 'estimated_days' => 1, 'message' => 'Incluye revisión, reparación y prueba final completa.'])->assertRedirect();

        $this->assertSame(1, $client->notifications()->count());
        $this->assertSame('Nueva propuesta recibida', $client->notifications()->firstOrFail()->data['title']);
    }
}
