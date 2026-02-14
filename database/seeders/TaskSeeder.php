<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            ['title' => 'Plan Q2 roadmap', 'effort_value' => 12, 'effort_unit' => EffortUnit::Hours, 'priority' => TaskPriority::High, 'status' => TaskStatus::Planned, 'start_offset' => 2, 'duration_hours' => 10],
            ['title' => 'Warehouse layout update', 'effort_value' => 3, 'effort_unit' => EffortUnit::Days, 'priority' => TaskPriority::Medium, 'status' => TaskStatus::InProgress, 'start_offset' => -1, 'duration_hours' => 16],
            ['title' => 'Customer onboarding flow review', 'effort_value' => 8, 'effort_unit' => EffortUnit::Hours, 'priority' => TaskPriority::Medium, 'status' => TaskStatus::Planned, 'start_offset' => 4, 'duration_hours' => 6],
            ['title' => 'Monthly capacity report', 'effort_value' => 5, 'effort_unit' => EffortUnit::Hours, 'priority' => TaskPriority::Low, 'status' => TaskStatus::Done, 'start_offset' => -8, 'duration_hours' => 4],
            ['title' => 'Safety compliance walkthrough', 'effort_value' => 6, 'effort_unit' => EffortUnit::Hours, 'priority' => TaskPriority::High, 'status' => TaskStatus::Planned, 'start_offset' => 6, 'duration_hours' => 5],
            ['title' => 'Ops tooling upgrade', 'effort_value' => 2, 'effort_unit' => EffortUnit::Days, 'priority' => TaskPriority::Urgent, 'status' => TaskStatus::InProgress, 'start_offset' => 1, 'duration_hours' => 14],
        ];

        foreach ($tasks as $task) {
            $startsAt = now()->addDays($task['start_offset'])->setTime(9, 0);
            $endsAt = $startsAt->copy()->addHours($task['duration_hours']);

            Task::query()->create([
                'title' => $task['title'],
                'description' => fake()->optional()->sentence(12),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'effort_value' => $task['effort_value'],
                'effort_unit' => $task['effort_unit'],
                'priority' => $task['priority'],
                'status' => $task['status'],
            ]);
        }
    }
}
