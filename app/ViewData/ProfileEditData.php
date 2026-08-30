<?php

namespace App\ViewData;

use App\Domain\Marketplace\Enums\BusinessDay;
use App\Domain\Marketplace\Enums\MarketplaceCapability;
use App\Domain\Marketplace\Enums\VendorStatus;
use App\Models\User;
use App\Models\VendorVerificationDocument;

final readonly class ProfileEditData
{
    /**
     * @param  array<string, mixed>  $businessHours
     * @param  list<string>  $businessDays
     * @param  array<string, string>  $dayLabels
     * @param  array<string, string>  $missingReviewRequirements
     * @param  list<int>  $interestCategoryIds
     * @param  array<string, VendorVerificationDocument>  $latestVerificationDocuments
     */
    public function __construct(
        public bool $hasCommercialProfile,
        public array $businessHours,
        public array $businessDays,
        public array $dayLabels,
        public ?string $vendorStatus,
        public string $vendorStatusLabel,
        public array $missingReviewRequirements,
        public array $interestCategoryIds,
        public array $latestVerificationDocuments,
        public string $providerCapability,
    ) {}

    public static function from(User $user): self
    {
        $vendor = $user->vendor;
        $businessHours = $vendor?->business_hours ?? [];
        $documents = $vendor?->verificationDocuments
            ->sortByDesc('id')
            ->unique('type')
            ->keyBy('type')
            ->all() ?? [];

        return new self(
            hasCommercialProfile: $vendor !== null,
            businessHours: $businessHours,
            businessDays: array_values($businessHours['days'] ?? [
                BusinessDay::Monday->value,
                BusinessDay::Tuesday->value,
                BusinessDay::Wednesday->value,
                BusinessDay::Thursday->value,
                BusinessDay::Friday->value,
            ]),
            dayLabels: BusinessDay::labels(),
            vendorStatus: $vendor?->status,
            vendorStatusLabel: VendorStatus::labelFor($vendor?->status),
            missingReviewRequirements: $vendor?->missingReviewRequirements() ?? [],
            interestCategoryIds: $user->interests->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            latestVerificationDocuments: $documents,
            providerCapability: MarketplaceCapability::Provider->value,
        );
    }
}
