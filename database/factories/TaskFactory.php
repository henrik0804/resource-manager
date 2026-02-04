<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 week', '+2 weeks');
        $endsAt = (clone $startsAt)->modify(sprintf('+%d hours', fake()->numberBetween(2, 72)));

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'effort_value' => fake()->randomFloat(1, 2, 40),
            'effort_unit' => fake()->randomElement(['hours', 'days', 'points']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => fake()->randomElement(['planned', 'in_progress', 'blocked', 'done']),
        ];
    }
}
