<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'description' => 'Full access to manage settings and data.'],
            ['name' => 'Planner', 'description' => 'Plans work, assigns resources, and manages timelines.'],
            ['name' => 'Manager', 'description' => 'Owns delivery outcomes and approves schedules.'],
            ['name' => 'Contributor', 'description' => 'Executes assigned work items.'],
            ['name' => 'Viewer', 'description' => 'Read-only access to schedules and reports.'],
        ];

        foreach ($roles as $role) {
            Role::query()->create($role);
        }
    }
}
