<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
        'slug',
        'description',
        'specialty',
        'service_area',
        'years_experience',
        'availability_status',
        'certifications',
        'tools',
        'phone',
        'email',
        'status',
        'verified_at',
        'latitude',
        'longitude',
        'address',
        'business_hours',
        'commission_rate_basis_points',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'business_hours' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
