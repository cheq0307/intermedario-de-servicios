<?php

namespace Tests\Feature;

use App\Models\JobRequest;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_open_own_notification(): void
    {
        $user = User::factory()->create();
        $user->notify(new MarketplaceActivity('Pedido listo', 'Ya puedes recogerlo.', 'dashboard'));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Pedido listo')
            ->assertSee('1 sin leer')
            ->assertSee('Nueva');
        $this->actingAs($user)->patch(route('notifications.open', $notification->id))->assertRedirect(route('dashboard'));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('No tienes avisos pendientes')
            ->assertSee('Leída');
    }

    public function test_notification_center_uses_simple_filters_and_keeps_conversations_separate(): void
    {
        $user = User::factory()->create();
        $user->notify(new MarketplaceActivity('Cuenta por verificar', 'Revisión administrativa pendiente.', 'dashboard', [], 'vendor_application'));
        $user->notify(new MarketplaceActivity('Nuevo seguidor', 'Una persona comenzó a seguirte.', 'profile.show', ['user' => $user->id], 'social_follow'));

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Todas')
            ->assertSee('Administrativas')
            ->assertSee('Sociales')
            ->assertDontSee('Conversaciones')
            ->assertSee('Cuenta por verificar')
            ->assertSee('Nuevo seguidor');

        $this->actingAs($user)->get(route('notifications.index', ['filter' => 'social']))
            ->assertOk()
            ->assertSee('Nuevo seguidor')
            ->assertDontSee('Cuenta por verificar');

        $this->actingAs($user)->get(route('notifications.index', ['filter' => 'administrative']))
            ->assertOk()
            ->assertSee('Cuenta por verificar')
            ->assertDontSee('Nuevo seguidor');

        $this->actingAs($user)->get(route('conversations.index'))
            ->assertOk()
            ->assertSee('Conversaciones')
            ->assertDontSee('Notificaciones');
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $owner->notify(new MarketplaceActivity('Privada', 'Solo para el dueño.', 'dashboard'));

        $this->actingAs($outsider)->patch(route('notifications.open', $owner->notifications()->firstOrFail()->id))->assertNotFound();
    }

    public function test_admin_dashboard_has_unified_filterable_notification_tray(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $admin->notify(new MarketplaceActivity('Nuevo caso de soporte', 'Un usuario necesita ayuda.', 'admin.support.index', [], 'support'));
        $admin->notify(new MarketplaceActivity('Nuevo seguidor', 'Una cuenta comenzó a seguirte.', 'profile.show', ['user' => $admin->id], 'social_follow'));

        $this->actingAs($admin)->get(route('admin.index'))
            ->assertOk()
            ->assertSee('data-notification-center', false)
            ->assertSee('data-notification-panel', false)
            ->assertSee('Administrativas')
            ->assertSee('Sociales')
            ->assertSee('Nuevo caso de soporte')
            ->assertSee('Nuevo seguidor')
            ->assertSee('data-notification-category="administrative"', false)
            ->assertSee('data-notification-category="social"', false)
            ->assertSee('Marcar todas leídas')
            ->assertSee('Ver todas las notificaciones');
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
