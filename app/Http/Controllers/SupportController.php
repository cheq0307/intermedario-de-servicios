<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\MarketplaceActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $tickets = $request->user()->supportTickets()
            ->with('assignedAdmin:id,name')->withCount('messages')
            ->latest('last_message_at')->paginate(20);

        return view('support.index', compact('tickets'));
    }

    public function create(Request $request): View
    {
        $category = array_key_exists((string) $request->query('category'), SupportTicket::CATEGORIES)
            ? (string) $request->query('category') : 'general';
        $subject = $category === 'provider_suspension' ? 'Solicito revisión de mi perfil suspendido' : '';

        return view('support.create', compact('category', 'subject'));
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
            abort_unless($user->vendor?->status === 'suspended', 422, 'Tu perfil no está suspendido.');
            $existing = $user->supportTickets()->where('category', 'provider_suspension')
                ->whereIn('status', ['open', 'in_progress', 'waiting_user'])->first();
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
                'status' => 'open',
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
        $isAdmin = $request->user()->hasAnyRole(['admin', 'superadmin']);
        abort_unless($isAdmin || $ticket->user_id === $request->user()->id, 403);
        $ticket->load(['user:id,name,email', 'vendor', 'assignedAdmin:id,name', 'messages.sender:id,name']);
        $ticket->messages()->where('sender_id', '!=', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return view('support.show', compact('ticket', 'isAdmin'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $isAdmin = $request->user()->hasAnyRole(['admin', 'superadmin']);
        abort_unless($isAdmin || $ticket->user_id === $request->user()->id, 403);
        abort_if($ticket->status === 'closed', 422, 'Este caso está cerrado.');
        $validated = $request->validate(['body' => ['required', 'string', 'min:2', 'max:5000']]);

        DB::transaction(function () use ($request, $ticket, $validated, $isAdmin): void {
            $ticket->messages()->create(['sender_id' => $request->user()->id, 'body' => $validated['body'], 'is_staff' => $isAdmin]);
            $ticket->update([
                'assigned_admin_id' => $isAdmin ? $request->user()->id : $ticket->assigned_admin_id,
                'status' => $isAdmin ? 'waiting_user' : ($ticket->status === 'resolved' ? 'open' : 'in_progress'),
                'last_message_at' => now(),
                'resolved_at' => null,
            ]);
        });

        if ($isAdmin) {
            $ticket->user->notify(new MarketplaceActivity('Soporte respondió tu solicitud', $ticket->reference.' · '.$ticket->subject, 'support.show', ['ticket' => $ticket->id], 'support_reply'));
        } else {
            $this->notifyStaff($ticket, 'Nueva respuesta de soporte', $ticket->reference.' recibió una respuesta del usuario.');
        }

        return back()->with('status', 'Respuesta enviada.');
    }

    public function adminIndex(Request $request): View
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['status' => ['nullable', Rule::in(array_keys(SupportTicket::STATUSES))], 'q' => ['nullable', 'string', 'max:100']]);
        $term = trim($filters['q'] ?? '');
        $tickets = SupportTicket::query()->with(['user:id,name,email', 'assignedAdmin:id,name'])->withCount('messages')
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($term !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query->where('subject', 'like', "%{$term}%")->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"))))
            ->latest('last_message_at')->paginate(25)->withQueryString();

        return view('admin.support.index', compact('tickets', 'filters'));
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $request->validate(['status' => ['required', Rule::in(array_keys(SupportTicket::STATUSES))]]);
        $ticket->update([
            'status' => $validated['status'],
            'assigned_admin_id' => $request->user()->id,
            'resolved_at' => $validated['status'] === 'resolved' ? now() : null,
        ]);
        $ticket->user->notify(new MarketplaceActivity('Soporte actualizó tu solicitud', $ticket->reference.' ahora está: '.$ticket->status_label.'.', 'support.show', ['ticket' => $ticket->id], 'support_status'));

        return back()->with('status', 'Estado del caso actualizado.');
    }

    private function notifyStaff(SupportTicket $ticket, string $title, string $body): void
    {
        User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', ['admin', 'superadmin']))
            ->whereKeyNot($ticket->user_id)->each(fn (User $admin) => $admin->notify(new MarketplaceActivity($title, $body, 'admin.support.show', ['ticket' => $ticket->id], 'support')));
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'superadmin']), 403);
    }
}
