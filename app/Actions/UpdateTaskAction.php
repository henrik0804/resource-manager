<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;

final class UpdateTaskAction
{
    /**
     * @param array{
     *   title?: string,
     *   description?: string|null,
     *   starts_at?: \DateTimeInterface|string,
     *   ends_at?: \DateTimeInterface|string,
     *   effort_value?: float|int|string,
     *   effort_unit?: string,
     *   priority?: string,
     *   status?: string
     * } $data
     */
    public function handle(Task $task, array $data): Task
    {
        $attributes = [];

        foreach (['title', 'description', 'starts_at', 'ends_at', 'effort_value', 'effort_unit', 'priority', 'status'] as $key) {
            if (array_key_exists($key, $data)) {
                $attributes[$key] = $data[$key];
            }
        }

        if ($attributes !== []) {
            $task->fill($attributes);
            $task->save();
        }

        return $task;
    }
}
