<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConflictType;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

final class ConflictDetectionService
{
    public function detect(
        Resource $resource,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
        float|int|string|null $allocationRatio = null,
        ?int $excludeAssignmentId = null,
    ): ConflictReport {
        $windowStartsAt = $this->toCarbon($startsAt);
        $windowEndsAt = $this->toCarbon($endsAt);

        if ($windowEndsAt->lessThanOrEqualTo($windowStartsAt)) {
            return new ConflictReport;
        }

        $report = new ConflictReport;
        $overlappingAssignments = $this->overlappingAssignments($resource, $windowStartsAt, $windowEndsAt, $excludeAssignmentId);

        $capacity = $this->resolveCapacity($resource);
        $requestedAllocation = $this->normalizeRatio($allocationRatio);
        $existingAllocation = $overlappingAssignments->sum(fn (TaskAssignment $assignment): float => $this->normalizeRatio($assignment->allocation_ratio));

        $totalAllocation = $requestedAllocation + $existingAllocation;

        if ($overlappingAssignments->isNotEmpty() && $totalAllocation > $capacity) {
            $report->add(ConflictType::DoubleBooked, [
                'related_ids' => $overlappingAssignments->pluck('id')->all(),
                'metrics' => [
                    'allocation' => $totalAllocation,
                    'capacity' => $capacity,
                    'capacity_unit' => $resource->capacity_unit?->value,
                    'existing_allocation' => $existingAllocation,
                    'requested_allocation' => $requestedAllocation,
                ],
            ]);
        }

        if ($requestedAllocation > $capacity) {
            $report->add(ConflictType::Overloaded, [
                'related_ids' => $overlappingAssignments->pluck('id')->all(),
                'metrics' => [
                    'allocation' => $requestedAllocation,
                    'capacity' => $capacity,
                    'capacity_unit' => $resource->capacity_unit?->value,
                ],
            ]);
        }

        $absences = ResourceAbsence::query()
            ->where('resource_id', $resource->id)
            ->where('starts_at', '<', $windowEndsAt)
            ->where('ends_at', '>', $windowStartsAt)
            ->get();

        foreach ($absences as $absence) {
            $report->add(ConflictType::Unavailable, [
                'related_ids' => [$absence->id],
            ]);
        }

        return $report;
    }

    /**
     * @return Collection<int, TaskAssignment>
     */
    private function overlappingAssignments(
        Resource $resource,
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        ?int $excludeAssignmentId = null,
    ): Collection {
        return TaskAssignment::query()
            ->where('resource_id', $resource->id)
            ->when($excludeAssignmentId, fn ($query, $id) => $query->where('id', '!=', $id))
            ->with('task')
            ->get()
            ->filter(function (TaskAssignment $assignment) use ($startsAt, $endsAt): bool {
                $assignmentStartsAt = $assignment->starts_at ?? $assignment->task?->starts_at;
                $assignmentEndsAt = $assignment->ends_at ?? $assignment->task?->ends_at;

                if ($assignmentStartsAt === null || $assignmentEndsAt === null) {
                    return false;
                }

                $assignmentStartsAt = $this->toCarbon($assignmentStartsAt);
                $assignmentEndsAt = $this->toCarbon($assignmentEndsAt);

                return $this->overlaps($startsAt, $endsAt, $assignmentStartsAt, $assignmentEndsAt);
            })
            ->values();
    }

    private function overlaps(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        CarbonImmutable $otherStartsAt,
        CarbonImmutable $otherEndsAt,
    ): bool {
        return $startsAt->lt($otherEndsAt) && $endsAt->gt($otherStartsAt);
    }

    /**
     * Resolve a resource's capacity to a numeric value usable for comparison.
     *
     * For HoursPerDay resources, capacity_value represents hours available per day.
     * For Slots resources, capacity_value represents the number of concurrent slots.
     * When no unit or value is set, defaults to 1.0 (single-use resource).
     */
    private function resolveCapacity(Resource $resource): float
    {
        $value = $resource->capacity_value;

        if ($value === null) {
            return 1.0;
        }

        $numericValue = (float) $value;

        return $numericValue > 0 ? $numericValue : 1.0;
    }

    private function normalizeRatio(float|int|string|null $ratio): float
    {
        if ($ratio === null) {
            return 1.0;
        }

        $value = (float) $ratio;

        if ($value < 0) {
            return 0.0;
        }

        return $value;
    }

    private function toCarbon(DateTimeInterface $dateTime): CarbonImmutable
    {
        if ($dateTime instanceof CarbonImmutable) {
            return $dateTime;
        }

        return CarbonImmutable::instance($dateTime);
    }
}
