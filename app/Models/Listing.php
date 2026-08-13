<?php

namespace App\Models;

use App\Domain\Marketplace\Enums\ListingType;
use App\Domain\Marketplace\Enums\PriceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Listing extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'type',
        'name',
        'slug',
        'description',
        'price_type',
        'price_amount',
        'currency',
        'stock',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => ListingType::class,
            'price_type' => PriceType::class,
            'is_active' => 'boolean',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function post(): HasOne
    {
        return $this->hasOne(Post::class);
    }

    public function inventoryReservations(): HasMany
    {
        return $this->hasMany(InventoryReservation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
