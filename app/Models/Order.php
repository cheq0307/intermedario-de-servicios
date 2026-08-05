<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'buyer_id',
        'vendor_id',
        'job_request_id',
        'job_proposal_id',
        'status',
        'fulfillment_type',
        'subtotal_amount',
        'commission_amount',
        'total_amount',
        'currency',
        'buyer_notes',
        'accepted_at',
        'started_at',
        'due_at',
        'delivered_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'due_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    public function jobProposal(): BelongsTo
    {
        return $this->belongsTo(JobProposal::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function dispute(): HasOne
    {
        return $this->hasOne(Dispute::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isParticipant(User $user): bool
    {
        return $this->buyer_id === $user->id || $this->vendor->user_id === $user->id;
    }
}
