<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['parent_id', 'name', 'slug', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_category_preferences')
            ->withPivot(['interest_score', 'behavior_score'])
            ->withTimestamps();
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'category_vendor')->withTimestamps();
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function jobRequests(): HasMany
    {
        return $this->hasMany(JobRequest::class);
    }
}
