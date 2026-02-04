<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Resource;
use App\Models\ResourceAbsence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResourceAbsence>
 */
class ResourceAbsenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('-1 month', '+1 month');
        $endsAt = (clone $startsAt)->modify(sprintf('+%d hours', fake()->numberBetween(4, 72)));

        return [
            'resource_id' => Resource::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'recurrence_rule' => fake()->optional()->randomElement([
                'FREQ=DAILY;COUNT=3',
                'FREQ=WEEKLY;COUNT=2',
                'FREQ=MONTHLY;COUNT=1',
            ]),
        ];
    }
}
