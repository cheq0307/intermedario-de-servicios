<?php

namespace Tests\Feature;

use App\Domain\Marketplace\Enums\SupportTicketStatus;
use App\Models\AdminUser;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_opens_ticket_and_administrator_is_notified(): void
    {
        $user = User::factory()->create();
        $admin = AdminUser::factory()->create();

        $this->actingAs($user, 'web')->post(route('support.store'), [
            'category' => 'account',
            'subject' => 'No puedo actualizar mis datos',
            'body' => 'El formulario no conserva el número de teléfono que escribí.',
        ])->assertRedirect();

        $ticket = SupportTicket::firstOrFail();
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame(SupportTicketStatus::Open, $ticket->status);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('support', $admin->notifications()->firstOrFail()->data['kind']);
    }

    public function test_only_owner_and_staff_can_view_or_reply_to_ticket(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $admin = AdminUser::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'category' => 'general',
            'subject' => 'Solicitud privada',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $ticket->messages()->create(['sender_id' => $owner->id, 'body' => 'Información privada del caso.', 'is_staff' => false]);

        $this->actingAs($outsider, 'web')->get(route('support.show', $ticket))->assertForbidden();
        $this->actingAs($outsider, 'web')->post(route('support.reply', $ticket), ['body' => 'No debo entrar'])->assertForbidden();
        $this->actingAs($owner, 'web')->get(route('support.show', $ticket))->assertOk()->assertSee('Información privada del caso.');
        $this->actingAs($admin, 'admin')->get(route('admin.support.show', $ticket))->assertOk()->assertSee($owner->email);
    }

    public function test_staff_reply_notifies_user_and_status_can_be_resolved(): void
    {
        $user = User::factory()->create();
        $admin = AdminUser::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'category' => 'payment',
            'subject' => 'Duda sobre un pago',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')->post(route('admin.support.reply', $ticket), [
            'body' => 'Estamos revisando el movimiento y te avisaremos aquí.',
        ])->assertRedirect();
        $this->assertSame(SupportTicketStatus::WaitingUser, $ticket->fresh()->status);
        $this->assertSame($admin->id, $ticket->fresh()->admin_user_id);
        $this->assertSame('support_reply', $user->notifications()->firstOrFail()->data['kind']);

        $this->actingAs($admin, 'admin')->patch(route('admin.support.status', $ticket), ['status' => 'resolved'])->assertRedirect();
        $this->assertSame(SupportTicketStatus::Resolved, $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);
    }

    public function test_owner_receives_only_new_support_messages_without_reloading(): void
    {
        $owner = User::factory()->create();
        $admin = AdminUser::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'category' => 'general',
            'subject' => 'Seguimiento conectado',
            'status' => 'waiting_user',
            'last_message_at' => now(),
        ]);
        $first = $ticket->messages()->create(['sender_id' => $owner->id, 'body' => 'Mensaje ya visible.', 'is_staff' => false]);
        $new = $ticket->messages()->create(['sender_id' => null, 'admin_user_id' => $admin->id, 'body' => 'Respuesta nueva de soporte.', 'is_staff' => true]);

        $this->actingAs($owner, 'web')
            ->getJson(route('support.messages.index', ['ticket' => $ticket, 'after_id' => $first->id]))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $new->id)
            ->assertJsonPath('messages.0.body', 'Respuesta nueva de soporte.')
            ->assertJsonPath('messages.0.is_staff', true)
            ->assertJsonPath('messages.0.is_mine', false)
            ->assertJsonPath('last_id', $new->id)
            ->assertJsonPath('ticket.status', 'waiting_user');

        $this->assertNotNull($new->fresh()->read_at);
    }

    public function test_outsider_cannot_poll_private_support_messages(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'category' => 'account',
            'subject' => 'Conversación privada',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($outsider, 'web')
            ->getJson(route('support.messages.index', $ticket))
            ->assertForbidden();
    }

    public function test_ajax_reply_returns_the_created_message_and_updated_ticket(): void
    {
        $owner = User::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'category' => 'general',
            'subject' => 'Respuesta sin recarga',
            'status' => 'resolved',
            'last_message_at' => now(),
            'resolved_at' => now(),
        ]);

        $this->actingAs($owner, 'web')
            ->postJson(route('support.reply', $ticket), ['body' => 'Necesito agregar otra información.'])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Necesito agregar otra información.')
            ->assertJsonPath('message.is_mine', true)
            ->assertJsonPath('ticket.status', 'open')
            ->assertJsonPath('ticket.is_closed', false);

        $this->assertDatabaseHas('support_messages', [
            'support_ticket_id' => $ticket->id,
            'sender_id' => $owner->id,
            'body' => 'Necesito agregar otra información.',
        ]);
    }

    public function test_suspended_provider_can_open_only_one_active_review(): void
    {
        $user = User::factory()->create();
        Vendor::create([
            'user_id' => $user->id,
            'display_name' => 'Proveedor en revisión',
            'slug' => 'proveedor-en-revision-'.$user->id,
            'status' => 'suspended',
            'suspension_reason' => 'Incumplimiento reportado por un usuario.',
            'suspended_at' => now(),
        ]);
        $payload = [
            'category' => 'provider_suspension',
            'subject' => 'Solicito revisión de mi perfil suspendido',
            'body' => 'Deseo aportar información y solicitar una nueva revisión administrativa.',
        ];

        $this->actingAs($user, 'web')->post(route('support.store'), $payload)->assertRedirect();
        $ticket = SupportTicket::firstOrFail();
        $this->actingAs($user, 'web')->post(route('support.store'), $payload)
            ->assertRedirect(route('support.show', $ticket));
        $this->assertDatabaseCount('support_tickets', 1);
    }
}
