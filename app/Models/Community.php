<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'municipality', 'state', 'postal_code', 'latitude', 'longitude', 'default_radius_km', 'is_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'default_radius_km' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        $place = $this->state ? "{$this->municipality}, {$this->state}" : $this->municipality;
        $distance = 'radio local de '.number_format((float) $this->default_radius_km, 1).' km';

        return "{$this->name} · {$place} · {$distance}";
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    public function distanceTo(self $community): ?float
    {
        if (! $this->hasCoordinates() || ! $community->hasCoordinates()) {
            return null;
        }

        $earthRadius = 6371;
        $latitudeDelta = deg2rad((float) $community->latitude - (float) $this->latitude);
        $longitudeDelta = deg2rad((float) $community->longitude - (float) $this->longitude);
        $originLatitude = deg2rad((float) $this->latitude);
        $targetLatitude = deg2rad((float) $community->latitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos($originLatitude) * cos($targetLatitude) * sin($longitudeDelta / 2) ** 2;
        $a = min(1, max(0, $a));

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
