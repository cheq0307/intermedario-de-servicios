<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_opens_ticket_and_administrator_is_notified(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        $this->actingAs($user)->post(route('support.store'), [
            'category' => 'account',
            'subject' => 'No puedo actualizar mis datos',
            'body' => 'El formulario no conserva el número de teléfono que escribí.',
        ])->assertRedirect();

        $ticket = SupportTicket::firstOrFail();
        $this->assertSame($user->id, $ticket->user_id);
        $this->assertSame('open', $ticket->status);
        $this->assertSame(1, $ticket->messages()->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('support', $admin->notifications()->firstOrFail()->data['kind']);
    }

    public function test_only_owner_and_staff_can_view_or_reply_to_ticket(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'category' => 'general',
            'subject' => 'Solicitud privada',
            'status' => 'open',
            'last_message_at' => now(),
        ]);
        $ticket->messages()->create(['sender_id' => $owner->id, 'body' => 'Información privada del caso.', 'is_staff' => false]);

        $this->actingAs($outsider)->get(route('support.show', $ticket))->assertForbidden();
        $this->actingAs($outsider)->post(route('support.reply', $ticket), ['body' => 'No debo entrar'])->assertForbidden();
        $this->actingAs($owner)->get(route('support.show', $ticket))->assertOk()->assertSee('Información privada del caso.');
        $this->actingAs($admin)->get(route('admin.support.show', $ticket))->assertOk()->assertSee($owner->email);
    }

    public function test_staff_reply_notifies_user_and_status_can_be_resolved(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));
        $ticket = SupportTicket::create([
            'user_id' => $user->id,
            'category' => 'payment',
            'subject' => 'Duda sobre un pago',
            'status' => 'open',
            'last_message_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('support.reply', $ticket), [
            'body' => 'Estamos revisando el movimiento y te avisaremos aquí.',
        ])->assertRedirect();
        $this->assertSame('waiting_user', $ticket->fresh()->status);
        $this->assertSame($admin->id, $ticket->fresh()->assigned_admin_id);
        $this->assertSame('support_reply', $user->notifications()->firstOrFail()->data['kind']);

        $this->actingAs($admin)->patch(route('admin.support.status', $ticket), ['status' => 'resolved'])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->resolved_at);
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

        $this->actingAs($user)->post(route('support.store'), $payload)->assertRedirect();
        $ticket = SupportTicket::firstOrFail();
        $this->actingAs($user)->post(route('support.store'), $payload)
            ->assertRedirect(route('support.show', $ticket));
        $this->assertDatabaseCount('support_tickets', 1);
    }
}
