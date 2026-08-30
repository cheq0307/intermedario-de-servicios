<?php

namespace App\Domain\Marketplace\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingUser = 'waiting_user';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Abierto',
            self::InProgress => 'En revisión',
            self::WaitingUser => 'Esperando respuesta del usuario',
            self::Resolved => 'Resuelto',
            self::Closed => 'Cerrado',
        };
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    /** @return list<string> */
    public static function activeValues(): array
    {
        return [self::Open->value, self::InProgress->value, self::WaitingUser->value];
    }
}
