<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AssignmentSource;
use App\Enums\ConflictType;
use App\Enums\TaskPriority;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use App\Services\ConflictDetectionService;
use App\Services\ConflictReport;
use App\Services\UtilizationService;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class AutoAssignAction
{
    public function __construct(
        private ConflictDetectionService $conflictDetection,
        private UtilizationService $utilizationService,
        private StoreTaskAssignmentAction $storeTaskAssignment,
    ) {}

    /**
     * @return array{assigned: int, skipped: int, suggestions: list<array{task: array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null}, resources: list<array{resource: array{id: int, name: string, utilization_percentage: float|null}, conflict_types: list<string>, blocking_assignments: list<array{id: int, task_id: int, task_title: string, task_priority: string, starts_at: string|null, ends_at: string|null, assignment_source: string}>}>}>}
     */
    public function handle(): array
    {
        $tasks = Task::query()
            ->whereDoesntHave('assignments')
            ->with('requirements')
            ->orderByRaw($this->priorityOrderSql())
            ->orderBy('starts_at')
            ->get();

        $assigned = 0;
        $skipped = 0;
        $suggestions = [];

        foreach ($tasks as $task) {
            if ($task->starts_at === null || $task->ends_at === null) {
                $skipped++;

                continue;
            }

            $candidateResources = $this->matchingResources($task->requirements);

            if ($candidateResources->isEmpty()) {
                $skipped++;

                continue;
            }

            $utilizationByResource = $this->utilizationByResource($task->starts_at, $task->ends_at);

            $rankedResources = $candidateResources
                ->sortBy(fn (Resource $resource) => $utilizationByResource[$resource->id] ?? PHP_FLOAT_MAX)
                ->values();

            $taskSuggestions = [];
            $assignedTask = false;

            foreach ($rankedResources as $resource) {
                $report = $this->conflictDetection->detect(
                    resource: $resource,
                    startsAt: $task->starts_at,
                    endsAt: $task->ends_at,
                );

                if (! $report->hasConflicts()) {
                    $this->storeTaskAssignment->handle([
                        'task_id' => $task->id,
                        'resource_id' => $resource->id,
                        'assignment_source' => AssignmentSource::Automated->value,
                    ]);

                    $assigned++;
                    $assignedTask = true;
                    break;
                }

                $blockingAssignments = $this->blockingAssignments($task, $report);

                if ($blockingAssignments->isEmpty()) {
                    continue;
                }

                $taskSuggestions[] = [
                    'resource' => $this->resourceSummary(
                        $resource,
                        $utilizationByResource[$resource->id] ?? null,
                    ),
                    'conflict_types' => $this->conflictTypes($report),
                    'blocking_assignments' => $blockingAssignments
                        ->map(fn (TaskAssignment $assignment) => $this->assignmentSummary($assignment))
                        ->values()
                        ->all(),
                ];
            }

            if (! $assignedTask) {
                $skipped++;

                if ($taskSuggestions !== []) {
                    $suggestions[] = [
                        'task' => $this->taskSummary($task),
                        'resources' => $taskSuggestions,
                    ];
                }
            }
        }

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
            'suggestions' => $suggestions,
        ];
    }

    private function priorityOrderSql(): string
    {
        return "case priority when 'urgent' then 1 when 'high' then 2 when 'medium' then 3 when 'low' then 4 else 5 end";
    }

    /**
     * @param  Collection<int, TaskRequirement>  $requirements
     * @return Collection<int, \App\Models\Resource>
     */
    private function matchingResources(Collection $requirements): Collection
    {
        $query = Resource::query();

        foreach ($requirements as $requirement) {
            $query->whereHas('resourceQualifications', function (Builder $qualificationQuery) use ($requirement): void {
                $qualificationQuery->where('qualification_id', $requirement->qualification_id);

                if ($requirement->required_level !== null) {
                    $qualificationQuery->whereIn('level', $requirement->required_level->levelsAtLeast());
                }
            });
        }

        return $query
            ->orderBy('name')
            ->get();
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

    /**
     * @return Collection<int, TaskAssignment>
     */
    private function blockingAssignments(Task $task, ConflictReport $report): Collection
    {
        $assignmentIds = $this->conflictAssignmentIds($report);

        if ($assignmentIds->isEmpty()) {
            return collect();
        }

        return TaskAssignment::query()
            ->with('task')
            ->whereIn('id', $assignmentIds)
            ->get()
            ->filter(function (TaskAssignment $assignment) use ($task): bool {
                $assignmentTask = $assignment->task;

                if ($assignmentTask === null) {
                    return false;
                }

                return $this->priorityRank($assignmentTask->priority) > $this->priorityRank($task->priority);
            });
    }

    /**
     * @return Collection<int, int>
     */
    private function conflictAssignmentIds(ConflictReport $report): Collection
    {
        $assignmentIds = collect();

        foreach ([ConflictType::DoubleBooked, ConflictType::Overloaded] as $type) {
            $assignmentIds = $assignmentIds->merge(
                $report
                    ->conflictsFor($type)
                    ->pluck('related_ids')
                    ->flatten()
                    ->filter()
            );
        }

        return $assignmentIds->unique()->values();
    }

    /**
     * @return list<string>
     */
    private function conflictTypes(ConflictReport $report): array
    {
        return collect($report->types())
            ->map(fn (ConflictType $type) => $type->value)
            ->values()
            ->all();
    }

    /**
     * @return array{id: int, title: string, priority: string, starts_at: string|null, ends_at: string|null}
     */
    private function taskSummary(Task $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'priority' => $task->priority->value,
            'starts_at' => $task->starts_at?->toDateTimeString(),
            'ends_at' => $task->ends_at?->toDateTimeString(),
        ];
    }

    /**
     * @return array{id: int, name: string, utilization_percentage: float|null}
     */
    private function resourceSummary(Resource $resource, ?float $utilization): array
    {
        return [
            'id' => $resource->id,
            'name' => $resource->name,
            'utilization_percentage' => $utilization,
        ];
    }

    /**
     * @return array{id: int, task_id: int, task_title: string, task_priority: string, starts_at: string|null, ends_at: string|null, assignment_source: string}
     */
    private function assignmentSummary(TaskAssignment $assignment): array
    {
        $task = $assignment->task;

        return [
            'id' => $assignment->id,
            'task_id' => $assignment->task_id,
            'task_title' => $task?->title ?? 'Unknown task',
            'task_priority' => $task?->priority?->value ?? 'unknown',
            'starts_at' => ($assignment->starts_at ?? $task?->starts_at)?->toDateTimeString(),
            'ends_at' => ($assignment->ends_at ?? $task?->ends_at)?->toDateTimeString(),
            'assignment_source' => $assignment->assignment_source->value,
        ];
    }

    private function priorityRank(?TaskPriority $priority): int
    {
        return match ($priority) {
            TaskPriority::Urgent => 1,
            TaskPriority::High => 2,
            TaskPriority::Medium => 3,
            TaskPriority::Low => 4,
            null => 5,
        };
    }
}
