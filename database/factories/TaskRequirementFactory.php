<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Task;
use App\Models\TaskRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskRequirement>
 */
class TaskRequirementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'qualification_id' => Qualification::factory(),
            'required_level' => fake()->optional()->randomElement(QualificationLevel::cases()),
        ];
    }
}
