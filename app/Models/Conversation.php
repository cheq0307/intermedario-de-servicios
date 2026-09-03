<?php

namespace App\Models;

use App\Models\Concerns\HasNegotiationLifecycle;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    use HasFactory, HasNegotiationLifecycle;

    protected $fillable = [
        'public_id',
        'direct_key',
        'order_id',
        'job_request_id',
        'type',
        'state',
        'last_message_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot(['last_read_at', 'last_read_message_id', 'muted_until'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    public function includesUser(User $user): bool
    {
        return $this->participants()->whereKey($user->id)->exists();
    }

    public function isOperation(): bool
    {
        return $this->type === 'operation';
    }

    public function acceptsMessages(): bool
    {
        return $this->state === 'active'
            && (! $this->isNegotiation() || ! $this->expires_at || $this->expires_at->isFuture());
    }

    public function archive(): void
    {
        $this->update([
            'state' => 'archived',
            'archived_at' => now(),
        ]);
    }
}
