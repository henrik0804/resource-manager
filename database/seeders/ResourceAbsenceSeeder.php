<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceAbsenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates absences for various resources to test:
     * - Conflict detection when assigning tasks during absences
     * - Automated assignment avoiding unavailable resources
     * - Utilization views showing time off
     */
    public function run(): void
    {
        $personTypeId = ResourceType::query()->where('name', 'Person')->value('id');
        $equipmentTypeId = ResourceType::query()->where('name', 'Equipment')->value('id');

        $personResources = Resource::query()
            ->when($personTypeId, fn ($query) => $query->where('resource_type_id', $personTypeId))
            ->get();

        $equipmentResources = Resource::query()
            ->when($equipmentTypeId, fn ($query) => $query->where('resource_type_id', $equipmentTypeId))
            ->get();

        // Person absences - create several to test different scenarios
        // Person 0: Urlaub nächste Woche (creates conflict potential)
        $person0 = $personResources->first();
        if ($person0) {
            ResourceAbsence::query()->create([
                'resource_id' => $person0->id,
                'starts_at' => now()->addDays(3)->startOfDay(),
                'ends_at' => now()->addDays(5)->endOfDay(),
                'recurrence_rule' => null,
            ]);
        }

        // Person 1: Krankheit (current)
        $person1 = $personResources->get(1);
        if ($person1) {
            ResourceAbsence::query()->create([
                'resource_id' => $person1->id,
                'starts_at' => now()->subDays(1)->startOfDay(),
                'ends_at' => now()->addDays(2)->endOfDay(),
                'recurrence_rule' => null,
            ]);
        }

        // Person 2: Fortbildung (affects qualification availability)
        $person2 = $personResources->get(2);
        if ($person2) {
            ResourceAbsence::query()->create([
                'resource_id' => $person2->id,
                'starts_at' => now()->addDays(7)->startOfDay(),
                'ends_at' => now()->addDays(9)->endOfDay(),
                'recurrence_rule' => null,
            ]);
        }

        // Equipment absences - create for testing room/equipment conflicts
        // Gabelstapler Wartung
        $gabelstapler = $equipmentResources->firstWhere('name', 'Gabelstapler #2');
        if ($gabelstapler) {
            ResourceAbsence::query()->create([
                'resource_id' => $gabelstapler->id,
                'starts_at' => now()->addDays(2)->startOfDay(),
                'ends_at' => now()->addDays(3)->endOfDay(),
                'recurrence_rule' => null,
            ]);
        }

        // Konferenzraum A already booked
        $roomTypeId = ResourceType::query()->where('name', 'Room')->value('id');
        $roomA = Resource::query()->where('resource_type_id', $roomTypeId)->where('name', 'Konferenzraum A')->first();
        if ($roomA) {
            ResourceAbsence::query()->create([
                'resource_id' => $roomA->id,
                'starts_at' => now()->addDays(1)->setTime(10, 0),
                'ends_at' => now()->addDays(1)->setTime(12, 0),
                'recurrence_rule' => null,
            ]);
        }
    }
}
