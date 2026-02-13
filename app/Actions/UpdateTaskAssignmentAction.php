<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskAssignment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateTaskAssignmentAction
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
     *   assignment_source?: string,
     *   assignee_status?: string|null
     * } $data
     */
    public function handle(TaskAssignment $assignment, array $data): TaskAssignment
    {
        return DB::transaction(function () use ($assignment, $data): TaskAssignment {
            if (array_key_exists('task_id', $data) && array_key_exists('task', $data)) {
                throw new InvalidArgumentException('Provide either task_id or task data, not both.');
            }

            if (array_key_exists('resource_id', $data) && array_key_exists('resource', $data)) {
                throw new InvalidArgumentException('Provide either resource_id or resource data, not both.');
            }

            $attributes = [];

            if (array_key_exists('task', $data)) {
                $attributes['task_id'] = $this->storeTask->handle($data['task'])->id;
            } elseif (array_key_exists('task_id', $data)) {
                $attributes['task_id'] = $data['task_id'];
            }

            if (array_key_exists('resource', $data)) {
                $attributes['resource_id'] = $this->storeResource->handle($data['resource'])->id;
            } elseif (array_key_exists('resource_id', $data)) {
                $attributes['resource_id'] = $data['resource_id'];
            }

            foreach (['starts_at', 'ends_at', 'allocation_ratio', 'assignment_source', 'assignee_status'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $data[$key];
                }
            }

            if ($attributes !== []) {
                $assignment->fill($attributes);
                $assignment->save();
            }

            return $assignment;
        });
    }
}
