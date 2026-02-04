<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAssignment>
 */
class TaskAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->optional()->dateTimeBetween('-1 week', '+2 weeks');
        $endsAt = $startsAt === null
            ? null
            : (clone $startsAt)->modify(sprintf('+%d hours', fake()->numberBetween(2, 48)));

        return [
            'task_id' => Task::factory(),
            'resource_id' => Resource::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'allocation_ratio' => fake()->optional()->randomFloat(2, 0.3, 1.0),
            'assignment_source' => fake()->randomElement(['manual', 'auto']),
            'assignee_status' => fake()->optional()->randomElement(['tentative', 'confirmed', 'declined']),
        ];
    }
}
