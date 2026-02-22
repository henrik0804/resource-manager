<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\CapacityHelper;
use App\Enums\ConflictType;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

final class ConflictDetectionService
{
    use CapacityHelper;

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
        $requestedAllocation = $this->normalizeAllocation($allocationRatio, $capacity);
        $existingAllocation = $overlappingAssignments->sum(fn (TaskAssignment $assignment): float => $this->normalizeAllocation($assignment->allocation_ratio, $capacity));

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

                return $this->overlapsDailyWindow($startsAt, $endsAt, $assignmentStartsAt, $assignmentEndsAt);
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
     * Treat multi-day assignments as repeating daily time windows.
     */
    private function overlapsDailyWindow(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
        CarbonImmutable $otherStartsAt,
        CarbonImmutable $otherEndsAt,
    ): bool {
        if ($endsAt->lte($startsAt) || $otherEndsAt->lte($otherStartsAt)) {
            return false;
        }

        $window = $this->dailyWindowDates($startsAt, $endsAt);
        $otherWindow = $this->dailyWindowDates($otherStartsAt, $otherEndsAt);

        if ($window === null || $otherWindow === null) {
            return false;
        }

        [$windowStart, $windowEnd] = $window;
        [$otherStart, $otherEnd] = $otherWindow;

        if ($windowEnd->lt($otherStart) || $otherEnd->lt($windowStart)) {
            return false;
        }

        $startMinutes = $this->minutesFromStartOfDay($startsAt);
        $endMinutes = $this->minutesFromStartOfDay($endsAt);
        $otherStartMinutes = $this->minutesFromStartOfDay($otherStartsAt);
        $otherEndMinutes = $this->minutesFromStartOfDay($otherEndsAt);

        if ($endMinutes <= $startMinutes || $otherEndMinutes <= $otherStartMinutes) {
            return $this->overlaps($startsAt, $endsAt, $otherStartsAt, $otherEndsAt);
        }

        return $startMinutes < $otherEndMinutes && $endMinutes > $otherStartMinutes;
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}|null
     */
    private function dailyWindowDates(
        CarbonImmutable $startsAt,
        CarbonImmutable $endsAt,
    ): ?array {
        if ($endsAt->lte($startsAt)) {
            return null;
        }

        $startDate = $startsAt->startOfDay();
        $endDate = $endsAt->startOfDay();

        if ($endsAt->equalTo($endDate)) {
            $endDate = $endDate->subDay();
        }

        if ($endDate->lt($startDate)) {
            return null;
        }

        return [$startDate, $endDate];
    }

    private function minutesFromStartOfDay(CarbonImmutable $dateTime): int
    {
        return ((int) $dateTime->format('H')) * 60 + (int) $dateTime->format('i');
    }
}
