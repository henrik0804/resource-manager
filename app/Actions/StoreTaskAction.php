<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Task;

final class StoreTaskAction
{
    /**
     * @param array{
     *   title: string,
     *   description?: string|null,
     *   starts_at: \DateTimeInterface|string,
     *   ends_at: \DateTimeInterface|string,
     *   effort_value: float|int|string,
     *   effort_unit: string,
     *   priority: string,
     *   status: string
     * } $data
     */
    public function handle(array $data): Task
    {
        return Task::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'effort_value' => $data['effort_value'],
            'effort_unit' => $data['effort_unit'],
            'priority' => $data['priority'],
            'status' => $data['status'],
        ]);
    }
}
