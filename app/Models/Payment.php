<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'provider_reference',
        'status',
        'method',
        'gross_amount',
        'commission_amount',
        'vendor_net_amount',
        'currency',
        'paid_at',
        'release_due_at',
        'released_at',
        'refunded_at',
        'provider_payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'release_due_at' => 'datetime',
            'released_at' => 'datetime',
            'refunded_at' => 'datetime',
            'provider_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
