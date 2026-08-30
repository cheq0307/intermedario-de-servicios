<?php

namespace App\Domain\Marketplace\Enums;

enum VendorStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Perfil en borrador',
            self::Pending => 'Solicitud enviada',
            self::Active => 'Proveedor aprobado',
            self::Rejected => 'Necesita cambios',
            self::Suspended => 'Perfil suspendido',
        };
    }

    public static function labelFor(?string $status): string
    {
        return self::tryFrom((string) $status)?->label() ?? 'Configurando servicios';
    }
}
