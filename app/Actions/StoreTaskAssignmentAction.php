<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskAssignment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreTaskAssignmentAction
{
    public function __construct(
        private StoreTaskAction $storeTask,
        private StoreResourceAction $storeResource,
    ) {}

    /**
     * @param array{
     *   task_id?: int|null,
     *   task?: array{
     *     title: string,
     *     description?: string|null,
     *     starts_at: \DateTimeInterface|string,
     *     ends_at: \DateTimeInterface|string,
     *     effort_value: float|int|string,
     *     effort_unit: string,
     *     priority: string,
     *     status: string
     *   }|null,
     *   resource_id?: int|null,
     *   resource?: array{
     *     name: string,
     *     resource_type_id?: int|null,
     *     resource_type?: array{name: string, description?: string|null}|null,
     *     capacity_value?: float|int|string|null,
     *     capacity_unit?: string|null,
     *     user_id?: int|null,
     *     user?: array{
     *       name: string,
     *       email: string,
     *       password: string,
     *       role_id?: int|null,
     *       role?: array{name: string, description?: string|null}|null
     *     }|null
     *   }|null,
     *   starts_at?: \DateTimeInterface|string|null,
     *   ends_at?: \DateTimeInterface|string|null,
     *   allocation_ratio?: float|int|string|null,
     *   assignment_source: string,
     *   assignee_status?: string|null
     * } $data
     */
    public function handle(array $data): TaskAssignment
    {
        return DB::transaction(function () use ($data): TaskAssignment {
            if (($data['task_id'] ?? null) !== null && array_key_exists('task', $data)) {
                throw new InvalidArgumentException('Provide either task_id or task data, not both.');
            }

            if (($data['resource_id'] ?? null) !== null && array_key_exists('resource', $data)) {
                throw new InvalidArgumentException('Provide either resource_id or resource data, not both.');
            }

            $taskId = $data['task_id'] ?? null;

            if ($taskId === null && isset($data['task'])) {
                $taskId = $this->storeTask->handle($data['task'])->id;
            }

            $resourceId = $data['resource_id'] ?? null;

            if ($resourceId === null && isset($data['resource'])) {
                $resourceId = $this->storeResource->handle($data['resource'])->id;
            }

            if ($taskId === null || $resourceId === null) {
                throw new InvalidArgumentException('Task and resource data are required to create a task assignment.');
            }

            return TaskAssignment::create([
                'task_id' => $taskId,
                'resource_id' => $resourceId,
                'starts_at' => $data['starts_at'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
                'allocation_ratio' => $data['allocation_ratio'] ?? null,
                'assignment_source' => $data['assignment_source'],
                'assignee_status' => $data['assignee_status'] ?? null,
            ]);
        });
    }
}
