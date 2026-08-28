<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\Marketplace\NegotiationConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $conversations = $request->user()->conversations()
            ->with([
                'participants:id,name,avatar_path,avatar_disk,account_type',
                'participants.roles:id,name',
                'order.jobRequest:id,public_id,title',
                'post.listing:id,post_id,name',
                'post.jobRequest:id,post_id,title',
                'order.items:id,order_id,name_snapshot',
                'messages' => fn ($query) => $query->with('sender:id,name')->latest()->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('conversations.updated_at')
            ->paginate(20);

        return view('conversations.index', compact('conversations'));
    }

    public function start(Request $request): RedirectResponse
    {
        abort_if(
            $request->user()->hasAnyRole(['admin', 'superadmin']) && ! $request->user()->canUseMarketplace(),
            422,
            'Las cuentas administrativas no inician conversaciones comerciales.',
        );

        $validated = $request->validate([
            'recipient_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNotNull('email_verified_at'),
                Rule::notIn([$request->user()->id]),
            ],
        ]);

        $participantIds = collect([$request->user()->id, (int) $validated['recipient_id']])->sort()->values();
        $directKey = $participantIds->implode(':');

        $conversation = DB::transaction(function () use ($participantIds, $directKey): Conversation {
            $conversation = Conversation::firstOrCreate(
                ['direct_key' => $directKey],
                ['public_id' => (string) Str::uuid()],
            );
            $conversation->participants()->syncWithoutDetaching($participantIds->all());

            return $conversation;
        });

        return redirect()->route('conversations.show', $conversation);
    }

    public function show(Request $request, Conversation $conversation, NegotiationConversationService $service): View
    {
        abort_unless($conversation->includesUser($request->user()), 403);

        $conversation = $service->expireIfNeeded($conversation);
        $conversation->load(['participants:id,name,avatar_path,avatar_disk,account_type', 'participants.roles:id,name', 'order.jobRequest', 'order.items', 'post.listing', 'post.jobRequest', 'agreementOrder']);
        $messages = $conversation->messages()
            ->with('sender:id,name,avatar_path,avatar_disk')
            ->latest()
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);
        $otherUser = $conversation->participants->firstWhere('id', '!=', $request->user()->id);
        $otherLastReadAt = $otherUser?->pivot?->last_read_at ? now()->parse($otherUser->pivot->last_read_at) : null;
        $supportConversation = (bool) ($otherUser?->hasAnyRole(['admin', 'superadmin']) && ! $otherUser?->canUseMarketplace());

        $operationOrder = $conversation->order;

        return view('conversations.show', compact('conversation', 'messages', 'otherUser', 'otherLastReadAt', 'supportConversation', 'operationOrder'));
    }

    public function store(Request $request, Conversation $conversation, NegotiationConversationService $service): RedirectResponse
    {
        abort_unless($conversation->includesUser($request->user()), 403);
        $conversation = $service->expireIfNeeded($conversation);
        abort_unless($conversation->acceptsMessages(), 422, 'Este chat ya no acepta mensajes. Consulta el expediente de disputa o el historial de la operación.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($conversation, $request, $validated): void {
            $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'type' => 'text',
                'body' => $validated['body'],
            ]);
            $conversation->update(['last_message_at' => now()]);
            $conversation->participants()->updateExistingPivot($request->user()->id, ['last_read_at' => now()]);
        });

        return redirect()->route('conversations.show', $conversation)->withFragment('ultimo-mensaje');
    }
}
