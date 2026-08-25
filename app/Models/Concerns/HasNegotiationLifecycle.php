<?php

namespace App\Models\Concerns;

use App\Models\Order;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasNegotiationLifecycle
{
    public function initializeHasNegotiationLifecycle(): void
    {
        $this->mergeFillable([
            'post_id',
            'initiated_by_user_id',
            'context_key',
            'agreement_order_id',
            'expires_at',
            'closed_at',
            'closed_reason',
            'retention_until',
            'extension_count',
        ]);
        $this->mergeCasts([
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
            'retention_until' => 'datetime',
            'extension_count' => 'integer',
        ]);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function agreementOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'agreement_order_id');
    }

    public function isNegotiation(): bool
    {
        return $this->type === 'negotiation';
    }
}
