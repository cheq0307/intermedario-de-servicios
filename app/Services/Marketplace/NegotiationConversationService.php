<?php

namespace App\Services\Marketplace;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NegotiationConversationService
{
    public function start(Post $post, User $initiator): Conversation
    {
        $participantIds = collect([$post->user_id, $initiator->id])->sort()->values();
        $contextKey = $this->contextKey($post->id, $participantIds->all());

        return DB::transaction(function () use ($post, $initiator, $participantIds, $contextKey): Conversation {
            $conversation = Conversation::firstOrCreate(
                ['context_key' => $contextKey],
                [
                    'public_id' => (string) Str::uuid(),
                    'post_id' => $post->id,
                    'job_request_id' => $post->job_request_id,
                    'initiated_by_user_id' => $initiator->id,
                    'type' => 'negotiation',
                    'state' => 'active',
                    'expires_at' => now()->addDays(config('marketplace.negotiation_days', 7)),
                ],
            );
            $conversation->participants()->syncWithoutDetaching($participantIds->all());

            if ($conversation->wasRecentlyCreated) {
                $conversation->messages()->create([
                    'sender_id' => $initiator->id,
                    'type' => 'system',
                    'body' => 'Se abrió una conversación independiente para “'.$this->postTitle($post).'”. Los acuerdos y pagos deben registrarse dentro de Plaza Local.',
                    'metadata' => ['post_id' => $post->id],
                ]);
                $conversation->update(['last_message_at' => now()]);
            }

            $conversation->refresh();

            return $conversation;
        });
    }

    public function expireIfNeeded(Conversation $conversation): Conversation
    {
        if ($conversation->isNegotiation() && $conversation->state === 'active' && $conversation->expires_at?->isPast()) {
            $senderId = $conversation->initiated_by_user_id ?: $conversation->participants()->value('users.id');
            $this->closeInternal($conversation, 'expired', $senderId, 'La conversación venció por inactividad. El historial quedó en modo de solo lectura.');
        }

        return $conversation->refresh();
    }

    public function extend(Conversation $conversation, User $actor): Conversation
    {
        return DB::transaction(function () use ($conversation, $actor): Conversation {
            $locked = Conversation::lockForUpdate()->findOrFail($conversation->id);
            $this->expireIfNeeded($locked);
            $locked->refresh();

            abort_unless($locked->isNegotiation() && $locked->state === 'active', 422, 'Esta conversación ya no se puede extender.');
            abort_if($locked->extension_count >= config('marketplace.negotiation_max_extensions', 1), 422, 'Esta conversación ya utilizó su extensión disponible.');
            abort_unless(
                $locked->expires_at?->lte(now()->addDays(config('marketplace.negotiation_extension_window_days', 2))),
                422,
                'La extensión se habilita durante las últimas 48 horas de la conversación.',
            );

            $locked->update([
                'expires_at' => $locked->expires_at->addDays(config('marketplace.negotiation_days', 7)),
                'extension_count' => $locked->extension_count + 1,
            ]);
            $this->systemMessage($locked, $actor->id, $actor->name.' extendió la conversación por siete días más.');

            return $locked->refresh();
        });
    }

    public function close(Conversation $conversation, User $actor): Conversation
    {
        return DB::transaction(function () use ($conversation, $actor): Conversation {
            $locked = Conversation::lockForUpdate()->findOrFail($conversation->id);
            abort_unless($locked->isNegotiation() && $locked->state === 'active', 422, 'Esta conversación ya está cerrada.');
            $this->closeInternal($locked, 'user_closed', $actor->id, $actor->name.' terminó la conversación sin registrar un acuerdo.');

            return $locked->refresh();
        });
    }

    public function closeForAgreement(Post $post, int $firstUserId, int $secondUserId, Order $order, int $actorId): void
    {
        $key = $this->contextKey($post->id, collect([$firstUserId, $secondUserId])->sort()->values()->all());
        $conversation = Conversation::where('context_key', $key)->where('state', 'active')->first();

        if (! $conversation) {
            return;
        }

        $this->closeInternal(
            $conversation,
            'agreement',
            $actorId,
            'El acuerdo pasó a una operación protegida. Continúen desde el chat del pedido.',
            $order,
        );
    }

    public function postTitle(Post $post): string
    {
        $post->loadMissing(['listing', 'jobRequest']);

        return $post->listing?->name ?? $post->jobRequest?->title ?? Str::limit($post->body, 60);
    }

    private function contextKey(int $postId, array $participantIds): string
    {
        return hash('sha256', 'post:'.$postId.':'.implode(':', $participantIds));
    }

    private function closeInternal(Conversation $conversation, string $reason, ?int $senderId, string $message, ?Order $order = null): void
    {
        if ($conversation->state !== 'active') {
            return;
        }

        if ($senderId) {
            $this->systemMessage($conversation, $senderId, $message);
        }
        $conversation->update([
            'context_key' => 'archived:'.Str::uuid().':'.$conversation->context_key,
            'state' => 'archived',
            'archived_at' => now(),
            'closed_at' => now(),
            'closed_reason' => $reason,
            'agreement_order_id' => $order?->id,
            'retention_until' => now()->addMonthsNoOverflow($order
                ? config('marketplace.agreement_conversation_retention_months', 6)
                : config('marketplace.negotiation_retention_months', 3)),
        ]);
    }

    private function systemMessage(Conversation $conversation, int $senderId, string $body): void
    {
        $conversation->messages()->create(['sender_id' => $senderId, 'type' => 'system', 'body' => $body]);
        $conversation->update(['last_message_at' => now()]);
    }
}
