<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConflictType;
use Illuminate\Support\Collection;

final class ConflictReport
{
    /**
     * @var array<string, list<array{related_ids: list<int>, metrics?: array<string, float|int|null>}>>
     */
    private array $conflicts = [];

    /**
     * @param  array{related_ids: list<int>, metrics?: array<string, float|int|null>}  $conflict
     */
    public function add(ConflictType $type, array $conflict): void
    {
        $this->conflicts[$type->value][] = $conflict;
    }

    public function hasConflicts(): bool
    {
        return $this->conflicts !== [];
    }

    /**
     * @return list<ConflictType>
     */
    public function types(): array
    {
        return array_map(
            ConflictType::from(...),
            array_keys($this->conflicts),
        );
    }

    /**
     * @return Collection<int, array{related_ids: list<int>, metrics?: array<string, float|int|null>}>
     */
    public function conflictsFor(ConflictType $type): Collection
    {
        /** @var list<array{related_ids: list<int>, metrics?: array<string, float|int|null>}> $conflicts */
        $conflicts = $this->conflicts[$type->value] ?? [];

        return collect($conflicts);
    }
}
