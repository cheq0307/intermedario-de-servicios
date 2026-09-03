<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Marketplace\NegotiationConversationService;
use App\Support\LiveUpdates;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConversationController extends Controller
{
    public function index(Request $request): View|JsonResponse|Response
    {
        $request->validate(['live_revision' => ['nullable', 'string', 'max:64']]);

        $conversations = $request->user()->conversations()
            ->with([
                'participants:id,name,avatar_path,avatar_disk,account_type',
                'participants.roles:id,name',
                'order.jobRequest:id,public_id,title',
                'post.listing:id,name',
                'post.jobRequest:id,title',
                'order.items:id,order_id,name_snapshot',
                'messages' => fn ($query) => $query->with('sender:id,name')->latest('id')->limit(1),
            ])
            ->withExists(['messages as has_unread_messages' => fn ($query) => $query
                ->where('sender_id', '!=', $request->user()->id)
                ->where(fn ($unread) => $unread
                    ->whereColumn('messages.id', '>', 'conversation_participants.last_read_message_id')
                    ->orWhere(fn ($legacy) => $legacy->whereNull('conversation_participants.last_read_message_id')
                        ->where(fn ($date) => $date->whereNull('conversation_participants.last_read_at')
                            ->orWhereColumn('messages.created_at', '>', 'conversation_participants.last_read_at'))))])
            ->orderByDesc('last_message_at')
            ->orderByDesc('conversations.updated_at')
            ->orderByDesc('conversations.id')
            ->paginate(20)
            ->appends($request->except('live_revision'));

        $revision = LiveUpdates::revision($conversations);
        if ($request->expectsJson() && hash_equals($revision, (string) $request->query('live_revision'))) {
            return response()->noContent()->header('Cache-Control', 'no-store, private');
        }

        if ($request->expectsJson()) {
            return response()->json([
                'html' => view('conversations._list', compact('conversations'))->render(),
                'revision' => $revision,
            ])->header('Cache-Control', 'no-store, private');
        }

        return view('conversations.index', compact('conversations', 'revision'));
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
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();

        $this->markReadThrough($conversation, $request->user()->id, $messages->last());
        $otherUser = $conversation->participants->firstWhere('id', '!=', $request->user()->id);
        $otherLastReadAt = $otherUser?->pivot?->last_read_at ? now()->parse($otherUser->pivot->last_read_at) : null;
        $otherLastReadMessageId = $otherUser?->pivot?->last_read_message_id;
        $supportConversation = (bool) ($otherUser?->hasAnyRole(['admin', 'superadmin']) && ! $otherUser?->canUseMarketplace());

        $operationOrder = $conversation->order;

        return view('conversations.show', compact('conversation', 'messages', 'otherUser', 'otherLastReadAt', 'otherLastReadMessageId', 'supportConversation', 'operationOrder'));
    }

    public function messages(Request $request, Conversation $conversation, NegotiationConversationService $service): JsonResponse
    {
        abort_unless($conversation->includesUser($request->user()), 403);
        $conversation = $service->expireIfNeeded($conversation);
        $validated = $request->validate(['after_id' => ['nullable', 'integer', 'min:0']]);
        $afterId = (int) ($validated['after_id'] ?? 0);
        $messages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('id')
            ->limit(101)
            ->get();
        $hasMore = $messages->count() > 100;
        $messages = $messages->take(100)->values();

        $this->markReadThrough($conversation, $request->user()->id, $messages->last());

        $otherParticipant = $conversation->participants()
            ->whereKeyNot($request->user()->id)
            ->first()?->pivot;
        $otherLastReadAt = $otherParticipant?->last_read_at;

        return response()->json([
            'messages' => $messages->map(fn (Message $message): array => $this->messagePayload($message, $request->user()->id))->all(),
            'last_id' => (int) ($messages->last()?->id ?? $afterId),
            'has_more' => $hasMore,
            'conversation' => [
                'state' => $conversation->state,
                'accepts_messages' => $conversation->acceptsMessages(),
                'other_last_read_at' => $otherLastReadAt ? Carbon::parse($otherLastReadAt)->toIso8601String() : null,
                'other_last_read_message_id' => $otherParticipant?->last_read_message_id,
            ],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Conversation $conversation, NegotiationConversationService $service): RedirectResponse|JsonResponse
    {
        abort_unless($conversation->includesUser($request->user()), 403);
        $conversation = $service->expireIfNeeded($conversation);
        abort_unless($conversation->acceptsMessages(), 422, 'Este chat ya no acepta mensajes. Consulta el expediente de disputa o el historial de la operación.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = DB::transaction(function () use ($conversation, $request, $validated): Message {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'type' => 'text',
                'body' => $validated['body'],
            ]);
            $conversation->update(['last_message_at' => now()]);

            return $message;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->messagePayload($message, $request->user()->id),
            ], 201);
        }

        return redirect()->route('conversations.show', $conversation)->withFragment('ultimo-mensaje');
    }

    /** @return array{id: int, body: string, type: string, is_mine: bool, sent_at: string, sent_at_iso: string} */
    private function messagePayload(Message $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'body' => (string) $message->body,
            'type' => $message->type->value,
            'is_mine' => $message->sender_id === $viewerId,
            'sent_at' => $message->created_at->format('H:i'),
            'sent_at_iso' => $message->created_at->toIso8601String(),
        ];
    }

    private function markReadThrough(Conversation $conversation, int $viewerId, ?Message $message): void
    {
        if (! $message) {
            return;
        }

        DB::table('conversation_participants')
            ->where('conversation_id', $conversation->id)->where('user_id', $viewerId)
            ->where(fn ($query) => $query->whereNull('last_read_message_id')->orWhere('last_read_message_id', '<', $message->id))
            ->update(['last_read_message_id' => $message->id, 'last_read_at' => $message->created_at, 'updated_at' => now()]);
    }
}
