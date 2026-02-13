<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QualificationLevel;
use App\Models\TaskRequirement;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreTaskRequirementAction
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
    public function handle(array $data): TaskRequirement
    {
        return DB::transaction(function () use ($data): TaskRequirement {
            if (($data['task_id'] ?? null) !== null && array_key_exists('task', $data)) {
                throw new InvalidArgumentException('Provide either task_id or task data, not both.');
            }

            if (($data['qualification_id'] ?? null) !== null && array_key_exists('qualification', $data)) {
                throw new InvalidArgumentException('Provide either qualification_id or qualification data, not both.');
            }

            $taskId = $data['task_id'] ?? null;

            if ($taskId === null && isset($data['task'])) {
                $taskId = $this->storeTask->handle($data['task'])->id;
            }

            $qualificationId = $data['qualification_id'] ?? null;

            if ($qualificationId === null && isset($data['qualification'])) {
                $qualificationId = $this->storeQualification->handle($data['qualification'])->id;
            }

            if ($taskId === null || $qualificationId === null) {
                throw new InvalidArgumentException('Task and qualification data are required to create a task requirement.');
            }

            return TaskRequirement::create([
                'task_id' => $taskId,
                'qualification_id' => $qualificationId,
                'required_level' => $data['required_level'] ?? null,
            ]);
        });
    }
}
