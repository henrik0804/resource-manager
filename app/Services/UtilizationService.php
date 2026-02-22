<?php

declare(strict_types=1);

namespace App\Services;

use App\Concerns\CapacityHelper;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;

final class UtilizationService
{
    use CapacityHelper;

    /**
     * Calculate utilization for all resources within a date range.
     *
     * Returns a unified structure suitable for bar chart rendering: each
     * resource carries a summary (overall utilization) and an array of
     * time-bucketed data points for a grouped/stacked breakdown.
     *
     * @return array{
     *     resources: list<array{
     *         id: int,
     *         name: string,
     *         resource_type: string|null,
     *         capacity_per_day: float,
     *         capacity_unit: string|null,
     *         summary: array{
     *             total_days: int,
     *             total_capacity: float,
     *             total_allocated: float,
     *             total_absent: float,
     *             available_capacity: float,
     *             utilization_percentage: float,
     *         },
     *         buckets: list<array{
     *             label: string,
     *             start: string,
     *             end: string,
     *             capacity: float,
     *             allocated: float,
     *             absent: float,
     *             available: float,
     *             utilization_percentage: float,
     *         }>,
     *     }>,
     *     period: array{start: string, end: string, granularity: string},
     * }
     */
    public function calculate(
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
        string $granularity = 'week',
    ): array {
        $start = $this->toCarbon($startsAt)->startOfDay();
        $end = $this->toCarbon($endsAt)->startOfDay();

        if ($end->lte($start)) {
            return [
                'resources' => [],
                'period' => [
                    'start' => $start->toDateString(),
                    'end' => $end->toDateString(),
                    'granularity' => $granularity,
                ],
            ];
        }

        $resources = Resource::query()
            ->with('resourceType')
            ->orderBy('name')
            ->get();

        $resourceIds = $resources->pluck('id');

        $assignments = $this->assignmentsInRange($resourceIds, $start, $end);
        $absences = $this->absencesInRange($resourceIds, $start, $end);
        $bucketRanges = $this->buildBuckets($start, $end, $granularity);

        $result = [];

        foreach ($resources as $resource) {
            $capacityPerDay = $this->resolveCapacity($resource);
            $resourceAssignments = $assignments->get($resource->id, collect());
            $resourceAbsences = $absences->get($resource->id, collect());

            $buckets = [];
            $totalCapacity = 0.0;
            $totalAllocated = 0.0;
            $totalAbsent = 0.0;

            foreach ($bucketRanges as $bucket) {
                $bucketData = $this->calculateBucket(
                    $bucket['start'],
                    $bucket['end'],
                    $capacityPerDay,
                    $resourceAssignments,
                    $resourceAbsences,
                );

                $bucketData['label'] = $bucket['label'];
                $buckets[] = $bucketData;

                $totalCapacity += $bucketData['capacity'];
                $totalAllocated += $bucketData['allocated'];
                $totalAbsent += $bucketData['absent'];
            }

            $availableCapacity = max($totalCapacity - $totalAbsent, 0.0);

            $result[] = [
                'id' => $resource->id,
                'name' => $resource->name,
                'resource_type' => $resource->resourceType?->name,
                'capacity_per_day' => $capacityPerDay,
                'capacity_unit' => $resource->capacity_unit?->value,
                'summary' => [
                    'total_days' => (int) $start->diffInDays($end),
                    'total_capacity' => round($totalCapacity, 2),
                    'total_allocated' => round($totalAllocated, 2),
                    'total_absent' => round($totalAbsent, 2),
                    'available_capacity' => round($availableCapacity, 2),
                    'utilization_percentage' => $availableCapacity > 0
                        ? round(($totalAllocated / $availableCapacity) * 100, 1)
                        : 0.0,
                ],
                'buckets' => $buckets,
            ];
        }

        return [
            'resources' => $result,
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'granularity' => $granularity,
            ],
        ];
    }

    /**
     * Calculate utilization metrics for a single time bucket.
     *
     * @param  Collection<int, TaskAssignment>  $assignments
     * @param  Collection<int, ResourceAbsence>  $absences
     * @return array{start: string, end: string, capacity: float, allocated: float, absent: float, available: float, utilization_percentage: float}
     */
    private function calculateBucket(
        CarbonImmutable $bucketStart,
        CarbonImmutable $bucketEnd,
        float $capacityPerDay,
        Collection $assignments,
        Collection $absences,
    ): array {
        $days = $this->countSpannedDays($bucketStart, $bucketEnd);
        $days = $days > 0 ? $days : 1;
        $capacity = $capacityPerDay * $days;

        $allocated = 0.0;

        foreach ($assignments as $assignment) {
            $assignmentStart = $assignment->starts_at ?? $assignment->task?->starts_at;
            $assignmentEnd = $assignment->ends_at ?? $assignment->task?->ends_at;

            if (! $assignmentStart || ! $assignmentEnd) {
                continue;
            }

            $overlapStart = $bucketStart->max($this->toCarbon($assignmentStart));
            $overlapEnd = $bucketEnd->min($this->toCarbon($assignmentEnd));

            if ($overlapStart->gte($overlapEnd)) {
                continue;
            }

            $overlapDays = $this->countSpannedDays($overlapStart, $overlapEnd);
            if ($overlapDays <= 0) {
                continue;
            }
            $allocated += $this->normalizeRatio($assignment->allocation_ratio) * $overlapDays;
        }

        $absentDays = 0.0;

        foreach ($absences as $absence) {
            $overlapStart = $bucketStart->max($this->toCarbon($absence->starts_at));
            $overlapEnd = $bucketEnd->min($this->toCarbon($absence->ends_at));

            if ($overlapStart->gte($overlapEnd)) {
                continue;
            }

            $overlapDays = $this->countSpannedDays($overlapStart, $overlapEnd);

            if ($overlapDays <= 0) {
                continue;
            }

            $absentDays += $overlapDays;
        }

        $absentDays = min($absentDays, $days);
        $absentCapacity = round($absentDays * $capacityPerDay, 2);
        $available = round(max($capacity - $absentCapacity, 0.0), 2);

        return [
            'start' => $bucketStart->toDateString(),
            'end' => $bucketEnd->toDateString(),
            'capacity' => round($capacity, 2),
            'allocated' => round($allocated, 2),
            'absent' => $absentCapacity,
            'available' => $available,
            'utilization_percentage' => $available > 0
                ? round(($allocated / $available) * 100, 1)
                : 0.0,
        ];
    }

    /**
     * Build time buckets for the given range and granularity.
     *
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable, label: string}>
     */
    private function buildBuckets(CarbonImmutable $start, CarbonImmutable $end, string $granularity): array
    {
        $buckets = [];
        $current = $start;

        while ($current->lt($end)) {
            $bucketEnd = match ($granularity) {
                'day' => $current->addDay(),
                'month' => $current->addMonth(),
                default => $current->addWeek(),
            };

            $bucketEnd = $bucketEnd->min($end);

            $label = match ($granularity) {
                'day' => $current->format('d.m.'),
                'month' => $current->translatedFormat('M Y'),
                default => $current->format('d.m.').' – '.$bucketEnd->subDay()->format('d.m.'),
            };

            $buckets[] = [
                'start' => $current,
                'end' => $bucketEnd,
                'label' => $label,
            ];

            $current = $bucketEnd;
        }

        return $buckets;
    }

    /**
     * Fetch all task assignments overlapping a date range, grouped by resource.
     *
     * @param  Collection<int, int>  $resourceIds
     * @return Collection<int, Collection<int, TaskAssignment>>
     */
    private function assignmentsInRange(Collection $resourceIds, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return TaskAssignment::query()
            ->with('task')
            ->whereIn('resource_id', $resourceIds)
            ->where(fn ($q) => $q
                ->where(fn ($inner) => $inner
                    ->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start))
                ->orWhereHas('task', fn ($tq) => $tq
                    ->where('starts_at', '<', $end)
                    ->where('ends_at', '>', $start)))
            ->get()
            ->groupBy('resource_id');
    }

    /**
     * Fetch all resource absences overlapping a date range, grouped by resource.
     *
     * @param  Collection<int, int>  $resourceIds
     * @return Collection<int, Collection<int, ResourceAbsence>>
     */
    private function absencesInRange(Collection $resourceIds, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return ResourceAbsence::query()
            ->whereIn('resource_id', $resourceIds)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->get()
            ->groupBy('resource_id');
    }
}
