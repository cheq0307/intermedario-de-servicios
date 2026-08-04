<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'comments_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
}
