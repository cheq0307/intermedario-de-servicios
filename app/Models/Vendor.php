<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'submitted_at',
        'reviewed_at',
        'rejection_reason',
        'suspension_reason',
        'suspended_at',
        'verified_at',
        'latitude',
        'longitude',
        'address',
        'business_hours',
        'commission_rate_basis_points',
        'stripe_account_id',
        'stripe_details_submitted',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'suspended_at' => 'datetime',
            'verified_at' => 'datetime',
            'business_hours' => 'array',
            'stripe_details_submitted' => 'boolean',
            'stripe_charges_enabled' => 'boolean',
            'stripe_payouts_enabled' => 'boolean',
        ];
    }

    /** @return array<string, string> */
    public function missingReviewRequirements(): array
    {
        $requirements = [
            'display_name' => 'nombre comercial',
            'description' => 'descripción profesional',
            'specialty' => 'especialidad',
            'service_area' => 'zona de servicio',
        ];

        $missing = collect($requirements)
            ->filter(fn (string $label, string $field): bool => blank($this->{$field}))
            ->all();

        if (! $this->businessHoursConfigured()) {
            $missing['business_hours'] = 'horario de atención';
        }

        return $missing;
    }

    public function isReadyForReview(): bool
    {
        return $this->user?->hasVerifiedEmail() === true
            && $this->missingReviewRequirements() === [];
    }

    public function businessHoursConfigured(): bool
    {
        $hours = $this->business_hours;

        return is_array($hours)
            && is_array($hours['days'] ?? null)
            && ! empty($hours['days'])
            && isset($hours['opens_at'], $hours['closes_at']);
    }

    public function isWithinBusinessHours(?CarbonInterface $moment = null): ?bool
    {
        if (! $this->businessHoursConfigured()) {
            return null;
        }

        $hours = $this->business_hours;
        $timezone = $hours['timezone'] ?? config('marketplace.business_timezone');
        $current = $moment
            ? CarbonImmutable::instance($moment)->setTimezone($timezone)
            : CarbonImmutable::now($timezone);

        if (! in_array(strtolower($current->englishDayOfWeek), $hours['days'], true)) {
            return false;
        }

        $time = $current->format('H:i');

        return $time >= $hours['opens_at'] && $time < $hours['closes_at'];
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

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }
}
