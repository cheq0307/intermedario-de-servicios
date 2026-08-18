<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobVacancy extends Model
{
    protected $fillable = ['public_id', 'employer_id', 'community_id', 'category_id', 'title', 'description', 'requirements', 'salary_min_amount', 'salary_max_amount', 'pay_period', 'work_mode', 'contract_type', 'schedule', 'vacancies_count', 'publication_fee_amount', 'currency', 'status', 'payment_reference', 'paid_at', 'published_at', 'expires_at', 'closed_at'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'published_at' => 'datetime', 'expires_at' => 'datetime', 'closed_at' => 'datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'published' && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
