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
     */
    public function run(): void
    {
        $personTypeId = ResourceType::query()->where('name', 'Person')->value('id');
        $resources = Resource::query()
            ->when($personTypeId, fn ($query) => $query->where('resource_type_id', $personTypeId))
            ->limit(3)
            ->get();

        if ($resources->isEmpty()) {
            return;
        }

        foreach ($resources as $resource) {
            $startsAt = now()->addDays(fake()->numberBetween(-10, 20))->startOfDay();
            $endsAt = $startsAt->copy()->addDays(fake()->numberBetween(1, 3))->endOfDay();

            ResourceAbsence::query()->create([
                'resource_id' => $resource->id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'recurrence_rule' => fake()->optional()->randomElement([
                    'FREQ=WEEKLY;COUNT=2',
                    'FREQ=MONTHLY;COUNT=1',
                ]),
            ]);
        }
    }
}
