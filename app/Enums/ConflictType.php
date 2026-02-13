<?php

declare(strict_types=1);

namespace App\Enums;

enum ConflictType: string
{
    case DoubleBooked = 'double_booked';
    case Overloaded = 'overloaded';
    case Unavailable = 'unavailable';
}
