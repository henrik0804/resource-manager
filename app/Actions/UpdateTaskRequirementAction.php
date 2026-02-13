<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QualificationLevel;
use App\Models\TaskRequirement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateTaskRequirementAction
{
    public function __construct(
        private StoreTaskAction $storeTask,
        private StoreQualificationAction $storeQualification,
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
     *   qualification_id?: int|null,
     *   qualification?: array{
     *     name: string,
     *     description?: string|null,
     *     resource_type_id?: int|null,
     *     resource_type?: array{name: string, description?: string|null}|null
     *   }|null,
     *   required_level?: QualificationLevel|string|null
     * } $data
     */
    public function handle(TaskRequirement $requirement, array $data): TaskRequirement
    {
        return DB::transaction(function () use ($requirement, $data): TaskRequirement {
            if (array_key_exists('task_id', $data) && array_key_exists('task', $data)) {
                throw new InvalidArgumentException('Provide either task_id or task data, not both.');
            }

            if (array_key_exists('qualification_id', $data) && array_key_exists('qualification', $data)) {
                throw new InvalidArgumentException('Provide either qualification_id or qualification data, not both.');
            }

            $attributes = [];

            if (array_key_exists('task', $data)) {
                $attributes['task_id'] = $this->storeTask->handle($data['task'])->id;
            } elseif (array_key_exists('task_id', $data)) {
                $attributes['task_id'] = $data['task_id'];
            }

            if (array_key_exists('qualification', $data)) {
                $attributes['qualification_id'] = $this->storeQualification->handle($data['qualification'])->id;
            } elseif (array_key_exists('qualification_id', $data)) {
                $attributes['qualification_id'] = $data['qualification_id'];
            }

            if (array_key_exists('required_level', $data)) {
                $attributes['required_level'] = $data['required_level'];
            }

            if ($attributes !== []) {
                $requirement->fill($attributes);
                $requirement->save();
            }

            return $requirement;
        });
    }
}
