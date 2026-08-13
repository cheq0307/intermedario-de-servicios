<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Community extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'municipality', 'state', 'distance_km', 'is_active'];

    protected function casts(): array
    {
        return [
            'distance_km' => 'decimal:2',
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
        $distance = (float) $this->distance_km === 0.0 ? 'sede' : number_format((float) $this->distance_km, 1).' km de la sede';

        return "{$this->name} · {$place} · {$distance}";
    }
}
