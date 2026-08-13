<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\JobRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'client_id',
        'category_id',
        'title',
        'description',
        'budget_min_amount',
        'budget_max_amount',
        'currency',
        'urgency',
        'status',
        'location_label',
        'latitude',
        'longitude',
        'published_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => JobRequestStatus::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function post(): HasOne
    {
        return $this->hasOne(Post::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(JobProposal::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class)->withTimestamps();
    }
}
