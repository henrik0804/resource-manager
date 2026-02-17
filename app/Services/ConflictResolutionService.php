<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskRequirement;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class ConflictResolutionService
{
    public function __construct(
        private ConflictDetectionService $conflictDetection,
        private UtilizationService $utilizationService,
    ) {}

    /**
     * @return Collection<int, array{starts_at: string, ends_at: string}>
     */
    public function alternativePeriods(
        Resource $resource,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
        float|int|string|null $allocationRatio = null,
        ?int $excludeAssignmentId = null,
        int $maxAlternatives = 3,
        int $searchWindowDays = 30,
    ): Collection {
        $start = $this->toCarbon($startsAt);
        $end = $this->toCarbon($endsAt);
        $durationMinutes = $start->diffInMinutes($end, false);

        if ($durationMinutes <= 0 || $maxAlternatives <= 0) {
            return collect();
        }

        $alternatives = collect();
        $offset = 1;
        $maxOffset = max($searchWindowDays, 1);

        while ($offset <= $maxOffset && $alternatives->count() < $maxAlternatives) {
            $candidateStart = $start->addDays($offset);
            $candidateEnd = $candidateStart->addMinutes($durationMinutes);

            $report = $this->conflictDetection->detect(
                resource: $resource,
                startsAt: $candidateStart,
                endsAt: $candidateEnd,
                allocationRatio: $allocationRatio,
                excludeAssignmentId: $excludeAssignmentId,
            );

            if (! $report->hasConflicts()) {
                $alternatives->push([
                    'starts_at' => $candidateStart->toDateTimeString(),
                    'ends_at' => $candidateEnd->toDateTimeString(),
                ]);
            }

            $offset++;
        }

        return $alternatives;
    }

    /**
     * @return Collection<int, resource>
     */
    public function alternatives(
        Resource $currentResource,
        DateTimeInterface $startsAt,
        DateTimeInterface $endsAt,
        ?Task $task = null,
        float|int|string|null $allocationRatio = null,
        ?int $excludeAssignmentId = null,
    ): Collection {
        $candidates = $this->candidateResources($currentResource, $task);

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        $utilizationByResource = $this->utilizationByResource($startsAt, $endsAt);

        return $candidates
            ->sortBy(fn (Resource $resource) => $utilizationByResource[$resource->id] ?? PHP_FLOAT_MAX)
            ->values()
            ->filter(function (Resource $resource) use ($startsAt, $endsAt, $allocationRatio, $excludeAssignmentId): bool {
                $report = $this->conflictDetection->detect(
                    resource: $resource,
                    startsAt: $startsAt,
                    endsAt: $endsAt,
                    allocationRatio: $allocationRatio,
                    excludeAssignmentId: $excludeAssignmentId,
                );

                return ! $report->hasConflicts();
            })
            ->values();
    }

    /**
     * @return Collection<int, resource>
     */
    private function candidateResources(Resource $currentResource, ?Task $task): Collection
    {
        $query = Resource::query()->where('id', '!=', $currentResource->id);

        if ($task === null) {
            return $this->queryByResourceType($query, $currentResource)->get();
        }

        $task->loadMissing('requirements');

        foreach ($task->requirements as $requirement) {
            $this->applyRequirementFilter($query, $requirement);
        }

        return $query->get();
    }

    private function queryByResourceType(Builder $query, Resource $currentResource): Builder
    {
        if ($currentResource->resource_type_id === null) {
            return $query->whereNull('resource_type_id');
        }

        return $query->where('resource_type_id', $currentResource->resource_type_id);
    }

    private function applyRequirementFilter(Builder $query, TaskRequirement $requirement): void
    {
        $query->whereHas('resourceQualifications', function (Builder $qualificationQuery) use ($requirement): void {
            $qualificationQuery->where('qualification_id', $requirement->qualification_id);

            if ($requirement->required_level !== null) {
                $qualificationQuery->whereIn('level', $requirement->required_level->levelsAtLeast());
            }
        });
    }

    /**
     * @return array<int, float>
     */
    private function utilizationByResource(DateTimeInterface $startsAt, DateTimeInterface $endsAt): array
    {
        $data = $this->utilizationService->calculate($startsAt, $endsAt);

        return collect($data['resources'])
            ->mapWithKeys(fn (array $resource) => [
                $resource['id'] => (float) $resource['summary']['utilization_percentage'],
            ])
            ->all();
    }

    private function toCarbon(DateTimeInterface $dateTime): CarbonImmutable
    {
        if ($dateTime instanceof CarbonImmutable) {
            return $dateTime;
        }

        return CarbonImmutable::instance($dateTime);
    }
}
