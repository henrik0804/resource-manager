<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AccessSection;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'section' => fake()->randomElement(AccessSection::cases()),
            'can_read' => fake()->boolean(),
            'can_write' => fake()->boolean(),
            'can_write_owned' => fake()->boolean(),
        ];
    }
}
