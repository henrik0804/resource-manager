<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskRequirement;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class ConflictResolutionService
{
    public function __construct(
        private ConflictDetectionService $conflictDetection,
        private UtilizationService $utilizationService,
    ) {}

    /**
     * @return Collection<int, \App\Models\Resource>
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
     * @return Collection<int, \App\Models\Resource>
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
}
