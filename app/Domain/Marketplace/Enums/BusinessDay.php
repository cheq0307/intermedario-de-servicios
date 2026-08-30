<?php

namespace App\Domain\Marketplace\Enums;

enum BusinessDay: string
{
    case Monday = 'monday';
    case Tuesday = 'tuesday';
    case Wednesday = 'wednesday';
    case Thursday = 'thursday';
    case Friday = 'friday';
    case Saturday = 'saturday';
    case Sunday = 'sunday';

    public function shortLabel(): string
    {
        return match ($this) {
            self::Monday => 'Lun',
            self::Tuesday => 'Mar',
            self::Wednesday => 'Mié',
            self::Thursday => 'Jue',
            self::Friday => 'Vie',
            self::Saturday => 'Sáb',
            self::Sunday => 'Dom',
        };
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $day): array => [$day->value => $day->shortLabel()])
            ->all();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
