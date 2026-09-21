<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\MessageDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReceipt extends Model
{
    protected $fillable = ['message_id', 'user_id', 'delivered_at', 'read_at'];

    protected function casts(): array
    {
        return ['delivered_at' => 'datetime', 'read_at' => 'datetime'];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryStatus(): MessageDeliveryStatus
    {
        if ($this->read_at !== null) {
            return MessageDeliveryStatus::Read;
        }

        return $this->delivered_at !== null ? MessageDeliveryStatus::Delivered : MessageDeliveryStatus::Sent;
    }
}
