<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceQualificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Assigns qualifications to both Person resources (employees) and
     * Equipment resources (non-employees) for testing various assignment scenarios.
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

        $personQualifications = Qualification::query()
            ->when(
                $personTypeId,
                fn ($query) => $query->whereNull('resource_type_id')->orWhere('resource_type_id', $personTypeId)
            )
            ->get();

        $equipmentQualifications = Qualification::query()
            ->when(
                $equipmentTypeId,
                fn ($query) => $query->whereNull('resource_type_id')->orWhere('resource_type_id', $equipmentTypeId)
            )
            ->get();

        $levels = QualificationLevel::cases();

        // Assign qualifications to Person resources (employees)
        foreach ($personResources as $resource) {
            $count = min(3, $personQualifications->count());
            $selection = collect($personQualifications->random(fake()->numberBetween(1, $count)));

            foreach ($selection as $qualification) {
                ResourceQualification::query()->create([
                    'resource_id' => $resource->id,
                    'qualification_id' => $qualification->id,
                    'level' => fake()->optional()->randomElement($levels),
                ]);
            }
        }

        // Assign qualifications to Equipment resources (non-employees)
        // Gabelstapler #2 needs Gabelstapler-Führerschein
        $gabelstapler = $equipmentResources->firstWhere('name', 'Gabelstapler #2');
        $gabelstaplerQual = $equipmentQualifications->firstWhere('name', 'Gabelstapler-Führerschein');
        if ($gabelstapler && $gabelstaplerQual) {
            ResourceQualification::query()->create([
                'resource_id' => $gabelstapler->id,
                'qualification_id' => $gabelstaplerQual->id,
                'level' => QualificationLevel::Advanced,
            ]);
        }

        // Beamer needs Audio-Einrichtung
        $beamer = $equipmentResources->firstWhere('name', 'Beamer');
        $audioQual = $equipmentQualifications->firstWhere('name', 'Audio-Einrichtung');
        if ($beamer && $audioQual) {
            ResourceQualification::query()->create([
                'resource_id' => $beamer->id,
                'qualification_id' => $audioQual->id,
                'level' => QualificationLevel::Intermediate,
            ]);
        }

        // 3D-Drucker needs a technical qualification (create implicit requirement)
        $printer = $equipmentResources->firstWhere('name', '3D-Drucker');
        if ($printer && $audioQual) {
            ResourceQualification::query()->create([
                'resource_id' => $printer->id,
                'qualification_id' => $audioQual->id,
                'level' => QualificationLevel::Beginner,
            ]);
        }
    }
}
