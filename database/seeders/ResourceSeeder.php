<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CapacityUnit;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class ResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $resourceTypes = ResourceType::query()->pluck('id', 'name');
        $personTypeId = $resourceTypes->get('Person');

        if ($personTypeId === null) {
            return;
        }

        $teamTypeId = $resourceTypes->get('Team', $personTypeId);
        $roomTypeId = $resourceTypes->get('Room', $personTypeId);
        $equipmentTypeId = $resourceTypes->get('Equipment', $personTypeId);

        $users = User::query()->limit(6)->get();
        foreach ($users as $user) {
            Resource::query()->create([
                'name' => $user->name,
                'resource_type_id' => $personTypeId,
                'capacity_value' => 8,
                'capacity_unit' => CapacityUnit::HoursPerDay,
                'user_id' => $user->id,
            ]);
        }

        Resource::query()->create([
            'name' => 'Design Team',
            'resource_type_id' => $teamTypeId,
            'capacity_value' => 5,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => 'Operations Team',
            'resource_type_id' => $teamTypeId,
            'capacity_value' => 4,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => 'Conference Room A',
            'resource_type_id' => $roomTypeId,
            'capacity_value' => 1,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => 'Workshop Bay',
            'resource_type_id' => $roomTypeId,
            'capacity_value' => 1,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => 'Forklift #2',
            'resource_type_id' => $equipmentTypeId,
            'capacity_value' => 1,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => '3D Printers',
            'resource_type_id' => $equipmentTypeId,
            'capacity_value' => 3,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => 'Projector Unit',
            'resource_type_id' => $equipmentTypeId,
            'capacity_value' => 1,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);

        Resource::query()->create([
            'name' => 'Meeting Room B',
            'resource_type_id' => $roomTypeId,
            'capacity_value' => 1,
            'capacity_unit' => CapacityUnit::Slots,
            'user_id' => null,
        ]);
    }
}
