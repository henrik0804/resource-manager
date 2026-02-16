<?php

declare(strict_types=1);

namespace App\Enums;

enum CapacityUnit: string
{
    case HoursPerDay = 'hours_per_day';
    case Slots = 'slots';

    public function label(): string
    {
        return match ($this) {
            self::HoursPerDay => 'Stunden/Tag',
            self::Slots => 'Slots (parallel)',
        };
    }
}
