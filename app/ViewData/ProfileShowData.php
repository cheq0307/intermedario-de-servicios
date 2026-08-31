<?php

namespace App\ViewData;

use App\Domain\Marketplace\Enums\VendorStatus;
use App\Models\User;
use App\Models\Vendor;

final readonly class ProfileShowData
{
    public function __construct(
        public bool $isOwner,
        public bool $isStaff,
        public bool $isProvider,
        public bool $showEmail,
        public ?Vendor $vendor,
        public string $displayName,
        public string $roleLabel,
        public string $intro,
        public string $description,
        public ?string $businessHoursLabel,
    ) {}

    public static function from(User $user, ?User $viewer, bool $isStaff): self
    {
        $vendor = $user->vendor;
        $isProvider = $vendor?->status === VendorStatus::Active->value;
        $staffLabel = $isStaff
            ? ($user->hasRole('superadmin') ? 'Superadministrador' : 'Administrador')
            : null;

        return new self(
            isOwner: $viewer?->is($user) === true,
            isStaff: $isStaff,
            isProvider: $isProvider,
            showEmail: $viewer?->is($user) === true || $viewer?->hasAnyRole(['admin', 'superadmin']) === true,
            vendor: $vendor,
            displayName: ! $isStaff && $isProvider ? ($vendor->display_name ?: $user->name) : $user->name,
            roleLabel: $staffLabel ?: $user->commercialRoleLabel(),
            intro: $isStaff
                ? 'Cuenta institucional de Plaza Local'
                : ($user->community?->public_location_label ?: 'Ubicación por definir'),
            description: $isStaff
                ? 'Esta cuenta representa al equipo de administración. No publica ofertas, no contrata servicios y no recibe reseñas comerciales.'
                : ($vendor?->description ?: $user->bio ?: 'Esta persona todavía está completando su presentación.'),
            businessHoursLabel: $isProvider ? $vendor->businessHoursLabel() : null,
        );
    }
}
