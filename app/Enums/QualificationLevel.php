<?php

declare(strict_types=1);

namespace App\Enums;

enum QualificationLevel: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    private const LEVEL_ORDER = [
        'beginner' => 1,
        'intermediate' => 2,
        'advanced' => 3,
        'expert' => 4,
    ];

    public function label(): string
    {
        return match ($this) {
            self::Beginner => 'Anfänger',
            self::Intermediate => 'Fortgeschritten',
            self::Advanced => 'Erfahren',
            self::Expert => 'Experte',
        };
    }

    /**
     * @return list<string>
     */
    public function levelsAtLeast(): array
    {
        $requiredRank = self::LEVEL_ORDER[$this->value] ?? 0;

        return array_values(
            array_keys(
                array_filter(self::LEVEL_ORDER, fn (int $rank) => $rank >= $requiredRank)
            )
        );
    }
}
