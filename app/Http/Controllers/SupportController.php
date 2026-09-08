<?php

namespace App\Http\Controllers;

use App\Domain\Marketplace\Enums\SupportTicketStatus;
use App\Domain\Marketplace\Enums\VendorStatus;
use App\Models\AdminUser;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\MarketplaceActivity;
use App\Support\LiveUpdates;
use App\ViewData\SupportThreadData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View|JsonResponse|Response
    {
        $query = $request->user()->supportTickets();
        $request->validate(['live_revision' => ['nullable', 'string', 'max:64']]);

        $tickets = $query
            ->with('assignedAdmin:id,name')->withCount('messages')
            ->latest('last_message_at')->orderByDesc('id')->paginate(20)
            ->appends($request->except('live_revision'));

        $revision = LiveUpdates::revision($tickets);
        if ($request->expectsJson() && hash_equals($revision, (string) $request->query('live_revision'))) {
            return response()->noContent()->header('Cache-Control', 'no-store, private');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('support._index-list', compact('tickets'))->render(),
                'revision' => $revision,
            ])->header('Cache-Control', 'no-store, private');
        }

        return view('support.index', compact('tickets', 'revision'));
    }

    public function create(Request $request): View
    {
        $category = array_key_exists((string) $request->query('category'), SupportTicket::CATEGORIES)
            ? (string) $request->query('category') : 'general';
        $subject = $category === 'provider_suspension' ? 'Solicito revisión de mi perfil suspendido' : '';
        $categories = SupportTicket::CATEGORIES;

        return view('support.create', compact('category', 'subject', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(SupportTicket::CATEGORIES))],
            'subject' => ['required', 'string', 'min:5', 'max:160'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        $user = $request->user();
        if ($validated['category'] === 'provider_suspension') {
            abort_unless($user->vendor?->status === VendorStatus::Suspended->value, 422, 'Tu perfil no está suspendido.');
            $existing = $user->supportTickets()->where('category', 'provider_suspension')
                ->whereIn('status', SupportTicketStatus::activeValues())->first();
            if ($existing) {
                return redirect()->route('support.show', $existing)->with('status', 'Ya existe una revisión activa para esta suspensión.');
            }
        }

        $ticket = DB::transaction(function () use ($user, $validated): SupportTicket {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'vendor_id' => $validated['category'] === 'provider_suspension' ? $user->vendor?->id : null,
                'category' => $validated['category'],
                'subject' => $validated['subject'],
                'status' => SupportTicketStatus::Open,
                'last_message_at' => now(),
            ]);
            $ticket->messages()->create(['sender_id' => $user->id, 'body' => $validated['body'], 'is_staff' => false]);

            return $ticket;
        });
        $this->notifyStaff($ticket, 'Nueva solicitud de soporte', $user->name.' abrió '.$ticket->reference.'.');

        return redirect()->route('support.show', $ticket)->with('status', 'Solicitud enviada a soporte.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        $isAdmin = $this->authorizeTicketAccess($request, $ticket);
        $ticket->load(['user:id,name,email', 'vendor', 'assignedAdmin:id,name', 'messages.sender:id,name', 'messages.admin:id,name']);
        $ticket->messages()->whereIn('id', $ticket->messages->modelKeys())->where('is_staff', ! ($request->user() instanceof AdminUser))->whereNull('read_at')->update(['read_at' => now()]);
        $support = SupportThreadData::from($ticket, $isAdmin);

        return view('support.show', compact('ticket', 'support'));
    }

    public function messages(Request $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicketAccess($request, $ticket);
        $validated = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $afterId = (int) ($validated['after_id'] ?? 0);
        $messages = $ticket->messages()
            ->with(['sender:id,name', 'admin:id,name'])
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(101)
            ->get();
        $hasMore = $messages->count() > 100;
        $messages = $messages->take(100)->values();

        $incomingUnreadIds = $messages
            ->where('is_staff', ! ($request->user() instanceof AdminUser))
            ->whereNull('read_at')
            ->pluck('id');
        if ($incomingUnreadIds->isNotEmpty()) {
            SupportMessage::query()->whereKey($incomingUnreadIds)->update(['read_at' => now()]);
        }

        $ticket->refresh();

        return response()->json([
            'messages' => $messages->map(fn (SupportMessage $message): array => $this->messagePayload($message, $request->user()))->all(),
            'last_id' => (int) ($messages->last()?->id ?? $afterId),
            'has_more' => $hasMore,
            'ticket' => $this->ticketPayload($ticket),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse|JsonResponse
    {
        $isAdmin = $this->authorizeTicketAccess($request, $ticket);
        abort_if($ticket->status === SupportTicketStatus::Closed, 422, 'Este caso está cerrado.');
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);

        $message = DB::transaction(function () use ($request, $ticket, $validated, $isAdmin): SupportMessage {
            $message = $ticket->messages()->create(['sender_id' => $isAdmin ? null : $request->user()->id, 'admin_user_id' => $isAdmin ? $request->user()->id : null, 'body' => $validated['body'], 'is_staff' => $isAdmin]);
            $ticket->update([
                'admin_user_id' => $isAdmin ? $request->user()->id : $ticket->admin_user_id,
                'status' => $isAdmin
                    ? SupportTicketStatus::WaitingUser
                    : ($ticket->status === SupportTicketStatus::Resolved ? SupportTicketStatus::Open : SupportTicketStatus::InProgress),
                'last_message_at' => now(),
                'resolved_at' => null,
            ]);

            return $message;
        });

        if ($isAdmin) {
            $ticket->user->notify(new MarketplaceActivity('Soporte respondió tu solicitud', $ticket->reference.' · '.$ticket->subject, 'support.show', ['ticket' => $ticket->id], 'support_reply'));
        } else {
            $this->notifyStaff($ticket, 'Nueva respuesta de soporte', $ticket->reference.' recibió una respuesta del usuario.');
        }

        if ($request->expectsJson()) {
            $message->load(['sender:id,name', 'admin:id,name']);
            $ticket->refresh();

            return response()->json([
                'message' => $this->messagePayload($message, $request->user()),
                'ticket' => $this->ticketPayload($ticket),
            ], 201);
        }

        return back()->with('status', 'Respuesta enviada.');
    }

    public function adminIndex(Request $request): View|JsonResponse|Response
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['status' => ['nullable', Rule::enum(SupportTicketStatus::class)], 'q' => ['nullable', 'string', 'max:100']]);
        $term = trim($filters['q'] ?? '');
        $query = SupportTicket::query()
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('subject', 'like', "%{$term}%")->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))));
        $request->validate(['live_revision' => ['nullable', 'string', 'max:64']]);

        $tickets = $query->with(['user:id,name,email', 'assignedAdmin:id,name'])->withCount('messages')
            ->latest('last_message_at')->orderByDesc('id')->paginate(25)->appends($request->except('live_revision'));

        $statuses = SupportTicketStatus::labels();

        $revision = LiveUpdates::revision($tickets);
        if ($request->expectsJson() && hash_equals($revision, (string) $request->query('live_revision'))) {
            return response()->noContent()->header('Cache-Control', 'no-store, private');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('admin.support._index-list', compact('tickets'))->render(),
                'revision' => $revision,
            ])->header('Cache-Control', 'no-store, private');
        }

        return view('admin.support.index', compact('tickets', 'filters', 'statuses', 'revision'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['status' => ['required', Rule::enum(SupportTicketStatus::class)]]);
        $status = SupportTicketStatus::from($validated['status']);
        $ticket->update([
            'status' => $status,
            'admin_user_id' => $request->user()->id,
            'resolved_at' => $status === SupportTicketStatus::Resolved ? now() : null,
        ]);
        $ticket->user->notify(new MarketplaceActivity('Soporte actualizó tu solicitud', $ticket->reference.' ahora está: '.$ticket->status_label.'.', 'support.show', ['ticket' => $ticket->id], 'support_status'));

        if ($request->expectsJson()) {
            return response()->json(['ticket' => $this->ticketPayload($ticket)])
                ->header('Cache-Control', 'no-store, private');
        }

        return back()->with('status', 'Estado del caso actualizado.');
    }

    private function notifyStaff(SupportTicket $ticket, string $title, string $body): void
    {
        AdminUser::query()->where('active', true)
            ->each(fn (AdminUser $admin) => $admin->notify(new MarketplaceActivity($title, $body, 'admin.support.show', ['ticket' => $ticket->id], 'support')));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
    }

    private function authorizeTicketAccess(Request $request, SupportTicket $ticket): bool
    {
        $isAdmin = $request->user()->hasAnyRole(['admin', 'superadmin']);
        abort_unless($isAdmin || $ticket->user_id === $request->user()->id, 403);

        return $isAdmin;
    }

    /** @return array{id: int, sender_name: string, body: string, is_staff: bool, is_mine: bool, sent_at: string, sent_at_iso: string} */
    private function messagePayload(SupportMessage $message, User|AdminUser $viewer): array
    {
        return [
            'id' => $message->id,
            'sender_name' => $message->admin?->name ?? $message->sender?->name ?? 'Soporte',
            'body' => $message->body,
            'is_staff' => $message->is_staff,
            'is_mine' => $viewer instanceof AdminUser ? $message->admin_user_id === $viewer->id : ($message->admin_user_id === null && $message->sender_id === $viewer->id),
            'sent_at' => $message->created_at->format('d/m/Y H:i'),
            'sent_at_iso' => $message->created_at->toIso8601String(),
        ];
    }

    /** @return array{status: string, status_label: string, is_closed: bool} */
    private function ticketPayload(SupportTicket $ticket): array
    {
        return [
            'status' => $ticket->status->value,
            'status_label' => $ticket->status_label,
            'is_closed' => $ticket->status === SupportTicketStatus::Closed,
        ];
    }
}
