<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vendor_id',
        'listing_id',
        'job_request_id',
        'submission_token',
        'type',
        'body',
        'comments_enabled',
        'published_at',
        'removed_at',
        'removed_by_user_id',
        'removal_reason',
    ];

    protected function casts(): array
    {
        return [
            'comments_enabled' => 'boolean',
            'published_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function jobRequest(): BelongsTo
    {
        return $this->belongsTo(JobRequest::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(PostMedia::class)->orderBy('position');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(PostReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class)->latest();
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PostShare::class);
    }

    public function promotions(): HasMany
    {
        return $this->hasMany(PostPromotion::class);
    }

    public function activePromotion(): HasOne
    {
        return $this->hasOne(PostPromotion::class)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->latestOfMany();
    }
}
