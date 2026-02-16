<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ConflictResolutionRequest;
use App\Models\Resource;
use App\Models\Task;
use App\Services\ConflictResolutionService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

final class ConflictResolutionController
{
    public function __invoke(ConflictResolutionRequest $request, ConflictResolutionService $service): JsonResponse
    {
        $validated = $request->validated();
        $currentResource = Resource::findOrFail($validated['current_resource_id']);
        $task = isset($validated['task_id']) ? Task::find($validated['task_id']) : null;

        $startsAt = $this->resolveStartsAt($validated, $task);
        $endsAt = $this->resolveEndsAt($validated, $task);

        if ($startsAt === null || $endsAt === null) {
            return response()->json(['alternatives' => []]);
        }

        $alternatives = $service->alternatives(
            currentResource: $currentResource,
            startsAt: $startsAt,
            endsAt: $endsAt,
            task: $task,
            allocationRatio: $validated['allocation_ratio'] ?? null,
            excludeAssignmentId: isset($validated['exclude_assignment_id'])
                ? (int) $validated['exclude_assignment_id']
                : null,
        );

        return response()->json([
            'alternatives' => $alternatives
                ->map(fn (Resource $resource) => [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'capacity_value' => $resource->capacity_value,
                    'capacity_unit' => $resource->capacity_unit?->value,
                ])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveStartsAt(array $validated, ?Task $task): ?CarbonImmutable
    {
        if (! empty($validated['starts_at'])) {
            return CarbonImmutable::parse($validated['starts_at']);
        }

        return $this->resolveTaskDate($task, 'starts_at');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function resolveEndsAt(array $validated, ?Task $task): ?CarbonImmutable
    {
        if (! empty($validated['ends_at'])) {
            return CarbonImmutable::parse($validated['ends_at']);
        }

        return $this->resolveTaskDate($task, 'ends_at');
    }

    private function resolveTaskDate(?Task $task, string $field): ?CarbonImmutable
    {
        if ($task === null || $task->{$field} === null) {
            return null;
        }

        return CarbonImmutable::instance($task->{$field});
    }
}
