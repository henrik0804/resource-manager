<?php

declare(strict_types=1);

namespace App\Enums;

enum ConflictType: string
{
    case DoubleBooked = 'double_booked';
    case Overloaded = 'overloaded';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::DoubleBooked => 'Doppelbuchung',
            self::Overloaded => 'Überlastung',
            self::Unavailable => 'Nicht verfügbar',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::DoubleBooked => 'Die parallele Belegung überschreitet die verfügbare Kapazität der Ressource.',
            self::Overloaded => 'Die angegebene Auslastung überschreitet die Kapazität der Ressource.',
            self::Unavailable => 'Die Ressource ist in diesem Zeitraum als abwesend eingetragen.',
        };
    }
}
