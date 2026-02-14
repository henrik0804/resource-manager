<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CapacityUnit;
use App\Models\Resource;
use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<resource>
 */
class ResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $capacityValue = fake()->optional()->randomFloat(2, 1, 40);

        return [
            'name' => fake()->unique()->company(),
            'resource_type_id' => ResourceType::factory(),
            'capacity_value' => $capacityValue,
            'capacity_unit' => $capacityValue === null ? null : fake()->randomElement(CapacityUnit::cases()),
            'user_id' => null,
        ];
    }
}
