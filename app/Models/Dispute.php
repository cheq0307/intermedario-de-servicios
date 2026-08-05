<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\DisputeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispute extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id', 'order_id', 'opened_by', 'reason', 'status', 'description',
        'order_status_before', 'resolution_outcome', 'resolution', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return ['status' => DisputeStatus::class, 'resolved_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(DisputeMessage::class);
    }
}
