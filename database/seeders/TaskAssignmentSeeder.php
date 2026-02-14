<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Database\Seeder;

class TaskAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = Task::query()->get();
        $personTypeId = ResourceType::query()->where('name', 'Person')->value('id');
        $resources = Resource::query()
            ->when($personTypeId, fn ($query) => $query->where('resource_type_id', $personTypeId))
            ->get();

        if ($tasks->isEmpty() || $resources->isEmpty()) {
            return;
        }

        foreach ($tasks as $task) {
            $count = min(2, $resources->count());
            $selection = collect($resources->random(fake()->numberBetween(1, $count)));

            foreach ($selection as $resource) {
                $startsAt = $task->starts_at->copy()->addHours(fake()->numberBetween(0, 4));
                $endsAt = $task->ends_at->copy()->subHours(fake()->numberBetween(0, 2));

                if ($endsAt->lessThanOrEqualTo($startsAt)) {
                    $endsAt = $startsAt->copy()->addHours(2);
                }

                TaskAssignment::query()->create([
                    'task_id' => $task->id,
                    'resource_id' => $resource->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'allocation_ratio' => fake()->optional()->randomFloat(2, 0.3, 1.0),
                    'assignment_source' => fake()->randomElement(AssignmentSource::cases()),
                    'assignee_status' => fake()->optional()->randomElement(AssigneeStatus::cases()),
                ]);
            }
        }
    }
}
