<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostPromotion extends Model
{
    protected $fillable = [
        'post_id', 'user_id', 'community_id', 'status', 'duration_days',
        'amount', 'currency', 'paid_at', 'starts_at', 'ends_at',
        'payment_provider', 'provider_preference_id', 'provider_payment_id',
        'checkout_url', 'payment_payload',
        'reviewed_by_user_id', 'admin_user_id', 'reviewed_at', 'impressions', 'clicks',
    ];

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'payment_payload' => 'array',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
