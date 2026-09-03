<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\MarketplaceActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LiveUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_requires_login_but_not_verified_email(): void
    {
        $this->getJson(route('activity.summary'))->assertUnauthorized();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->getJson(route('activity.summary'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('unread_notifications', 0);
    }

    public function test_summary_is_private_bounded_and_does_not_mark_notifications_read(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            $user->notify(new MarketplaceActivity('Aviso propio '.$i, '<script>alert(1)</script>', 'dashboard'));
        }
        $other->notify(new MarketplaceActivity('Aviso secreto ajeno', 'Privado', 'dashboard'));
        $response = $this->actingAs($user)->getJson(route('activity.summary'))->assertOk()
            ->assertJsonPath('unread_notifications', 10)
            ->assertJsonPath('unread_conversations', 0);
        $html = $response->json('preview_html');
        $this->assertSame(8, substr_count($html, 'data-notification-item'));
        $this->assertStringNotContainsString('Aviso secreto ajeno', $html);
        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertSame(10, $user->unreadNotifications()->count());
    }

    public function test_notification_list_preserves_filter_and_detects_same_second_read_changes(): void
    {
        $this->freezeTime();
        $user = User::factory()->create();
        $user->notify(new MarketplaceActivity('Seguidor visible', 'Social', 'dashboard', [], 'social_follow'));
        $user->notify(new MarketplaceActivity('Soporte oculto', 'Administrativo', 'dashboard', [], 'support'));
        $url = route('notifications.index', ['filter' => 'social']);
        $response = $this->actingAs($user)->getJson($url)->assertOk();
        $this->assertStringContainsString('Seguidor visible', $response->json('html'));
        $this->assertStringNotContainsString('Soporte oculto', $response->json('html'));
        $revision = $response->json('revision');
        $this->getJson($url.'&live_revision='.$revision)->assertNoContent()
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->postJson(route('notifications.read-all'), ['_method' => 'PATCH'])->assertOk()
            ->assertJsonPath('unread_notifications', 0);
        $this->getJson($url.'&live_revision='.$revision)->assertOk();
    }

    public function test_notification_pagination_remains_bounded_and_keeps_filter(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 23; $i++) {
            $user->notify(new MarketplaceActivity('Notificación social', 'Social', 'dashboard', [], 'social_follow'));
        }
        $response = $this->actingAs($user)->getJson(route('notifications.index', ['filter' => 'social', 'page' => 2]))->assertOk();
        $this->assertSame(3, substr_count($response->json('html'), 'Notificación social'));
        $this->assertStringContainsString('filter=social', $response->json('html'));
    }

    public function test_conversation_updates_are_private_and_require_verification(): void
    {
        [$sender, $recipient, $chat] = $this->chat();
        $chat->messages()->create(['sender_id' => $sender->id, 'type' => 'text', 'body' => 'Privado']);
        $this->actingAs(User::factory()->create())->getJson(route('conversations.messages.index', $chat))->assertForbidden();
        $this->actingAs(User::factory()->unverified()->create())->getJson(route('conversations.messages.index', $chat))->assertForbidden();
        $this->actingAs($recipient)->getJson(route('conversations.messages.index', ['conversation' => $chat, 'after_id' => -1]))->assertUnprocessable();
    }

    public function test_conversation_batches_only_mark_returned_messages_read_even_in_same_second(): void
    {
        $this->freezeTime();
        [$sender, $recipient, $chat] = $this->chat();
        for ($i = 0; $i < 101; $i++) {
            $chat->messages()->create(['sender_id' => $sender->id, 'type' => 'text', 'body' => 'Mensaje '.$i]);
        }
        $response = $this->actingAs($recipient)->getJson(route('conversations.messages.index', $chat))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(100, 'messages')->assertJsonPath('has_more', true);
        $last = $response->json('last_id');
        $this->assertSame(1, $recipient->unreadConversationsCount());
        $this->assertDatabaseHas('conversation_participants', ['conversation_id' => $chat->id, 'user_id' => $recipient->id, 'last_read_message_id' => $last]);
        $this->getJson(route('conversations.messages.index', ['conversation' => $chat, 'after_id' => $last]))->assertOk()
            ->assertJsonCount(1, 'messages')->assertJsonPath('has_more', false);
        $this->assertSame(0, $recipient->unreadConversationsCount());

        $this->actingAs($sender)->getJson(route('conversations.messages.index', ['conversation' => $chat, 'after_id' => $chat->messages()->max('id')]))
            ->assertJsonPath('conversation.other_last_read_message_id', $chat->messages()->max('id'));
        // A late response from an older request must never move the read cursor backwards.
        $this->actingAs($recipient)->getJson(route('conversations.messages.index', $chat))->assertOk();
        $this->assertSame(0, $recipient->unreadConversationsCount());
    }

    public function test_sending_does_not_mark_unseen_incoming_messages_read(): void
    {
        [$sender, $recipient, $chat] = $this->chat();
        $incoming = $chat->messages()->create(['sender_id' => $sender->id, 'type' => 'text', 'body' => 'Llegó mientras escribía']);
        $response = $this->actingAs($recipient)->postJson(route('conversations.messages.store', $chat), ['body' => 'Mi respuesta'])->assertCreated();
        $this->assertSame(1, $recipient->unreadConversationsCount());
        $this->assertGreaterThan($incoming->id, $response->json('message.id'));
        $this->getJson(route('conversations.index'))->assertOk()->assertJson(fn ($json) => $json->whereType('html', 'string')->etc());
        $this->getJson(route('conversations.messages.index', $chat))->assertOk()->assertJsonCount(2, 'messages');
        $this->assertSame(0, $recipient->unreadConversationsCount());
    }

    public function test_list_revisions_detect_new_messages_and_status_with_frozen_time(): void
    {
        $this->freezeTime();
        [$sender, $recipient, $chat] = $this->chat();
        $this->actingAs($sender)->postJson(route('conversations.messages.store', $chat), ['body' => 'Primero'])->assertCreated();
        $first = $this->actingAs($recipient)->getJson(route('conversations.index'))->assertOk()->json('revision');
        $this->getJson(route('conversations.index', ['live_revision' => $first]))->assertNoContent();
        $this->actingAs($sender)->postJson(route('conversations.messages.store', $chat), ['body' => 'Segundo'])->assertCreated();
        $second = $this->actingAs($recipient)->getJson(route('conversations.index', ['live_revision' => $first]))->assertOk()->json('revision');
        $this->assertNotSame($first, $second);
        $chat->update(['state' => 'archived']);
        $this->getJson(route('conversations.index', ['live_revision' => $second]))->assertOk();
    }

    public function test_closed_chat_returns_state_and_rejects_sends_without_losing_history(): void
    {
        [$sender, , $chat] = $this->chat();
        $chat->update(['state' => 'archived']);
        $this->actingAs($sender)->getJson(route('conversations.messages.index', $chat))->assertOk()
            ->assertJsonPath('conversation.accepts_messages', false)->assertJsonPath('conversation.state', 'archived');
        $this->postJson(route('conversations.messages.store', $chat), ['body' => 'No enviar'])->assertUnprocessable();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_support_lists_are_scoped_and_detect_same_second_status_updates(): void
    {
        $this->freezeTime();
        $owner = User::factory()->create();
        $admin = $this->admin();
        $ticket = $this->ticket($owner, 'Mi caso privado');
        $this->ticket(User::factory()->create(), 'Ajeno secreto');
        $first = $this->actingAs($owner)->getJson(route('support.index'))->assertOk();
        $this->assertStringNotContainsString('Ajeno secreto', $first->json('html'));
        $revision = $first->json('revision');
        $this->getJson(route('support.index', ['live_revision' => $revision]))->assertNoContent();
        $this->getJson(route('admin.support.index'))->assertForbidden();
        $this->actingAs($admin)->postJson(route('admin.support.status', $ticket), ['_method' => 'PATCH', 'status' => 'closed'])->assertOk()
            ->assertJsonPath('ticket.is_closed', true);
        $this->actingAs($owner)->getJson(route('support.index', ['live_revision' => $revision]))->assertOk();
        $this->getJson(route('support.messages.index', $ticket))->assertJsonPath('ticket.is_closed', true);
        $this->postJson(route('support.reply', $ticket), ['body' => 'No enviar'])->assertUnprocessable();
        $this->actingAs($admin)->postJson(route('admin.support.status', $ticket), ['_method' => 'PATCH', 'status' => 'open'])->assertOk();
        $this->actingAs($owner)->getJson(route('support.messages.index', $ticket))->assertJsonPath('ticket.is_closed', false);
    }

    public function test_admin_support_filter_retains_pagination_and_revision(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        for ($i = 0; $i < 27; $i++) {
            $this->ticket($owner, 'Caso prueba '.$i);
        }
        $this->ticket($owner, 'Caso excluido')->update(['status' => 'closed']);
        $url = route('admin.support.index', ['status' => 'open', 'q' => 'prueba', 'page' => 2]);
        $first = $this->actingAs($admin)->getJson($url)->assertOk();
        $this->assertSame(2, substr_count($first->json('html'), 'Caso prueba'));
        $this->assertStringNotContainsString('Caso excluido', $first->json('html'));
        $this->assertStringContainsString('status=open', $first->json('html'));
        $this->getJson($url.'&live_revision='.$first->json('revision'))->assertNoContent();
    }

    public function test_support_batches_do_not_mark_omitted_messages_read(): void
    {
        $owner = User::factory()->create();
        $admin = $this->admin();
        $ticket = $this->ticket($owner);
        for ($i = 0; $i < 101; $i++) {
            $ticket->messages()->create(['sender_id' => $admin->id, 'body' => 'Respuesta '.$i, 'is_staff' => true]);
        }
        $response = $this->actingAs($owner)->getJson(route('support.messages.index', $ticket))->assertOk()
            ->assertJsonCount(100, 'messages')->assertJsonPath('has_more', true);
        $this->assertSame(1, $ticket->messages()->whereNull('read_at')->count());
        $this->getJson(route('support.messages.index', ['ticket' => $ticket, 'after_id' => $response->json('last_id')]))->assertJsonCount(1, 'messages');
        $this->assertSame(0, $ticket->messages()->whereNull('read_at')->count());
    }

    public function test_admin_metrics_are_protected_and_change_without_loading_dashboard(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->getJson(route('admin.summary'))->assertForbidden();
        $admin = $this->admin();
        $this->actingAs($admin)->getJson(route('admin.summary'))->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('metrics.open_support_tickets', 0);
        $this->ticket($owner);
        Vendor::create(['user_id' => $owner->id, 'display_name' => 'Pendiente', 'slug' => 'pendiente', 'status' => 'pending', 'submitted_at' => now()]);
        $this->getJson(route('admin.summary'))->assertOk()->assertJsonPath('metrics.open_support_tickets', 1)
            ->assertJsonPath('metrics.pending_vendors', 1);
    }

    public function test_inactive_account_cannot_continue_polling_private_data(): void
    {
        $user = User::factory()->create(['account_status' => 'suspended']);
        $this->actingAs($user)->getJson(route('activity.summary'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function chat(): array
    {
        $sender = User::factory()->create();
        $recipient = User::factory()->create();
        $chat = Conversation::create(['public_id' => (string) Str::uuid(), 'type' => 'direct', 'state' => 'active']);
        $chat->participants()->attach([$sender->id, $recipient->id]);

        return [$sender, $recipient, $chat];
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin'));

        return $admin;
    }

    private function ticket(User $user, string $subject = 'Caso para pruebas'): SupportTicket
    {
        return SupportTicket::create(['user_id' => $user->id, 'category' => 'general', 'subject' => $subject, 'status' => 'open', 'last_message_at' => now()]);
    }
}
