<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use Carbon\CarbonImmutable;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('conflict-resolution falls back to task dates and orders alternatives by utilization', function (): void {
    $resourceType = ResourceType::factory()->create();
    $qualification = Qualification::factory()->create([
        'resource_type_id' => $resourceType->id,
    ]);

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-03-01 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-03-03 18:00:00'),
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $currentResource = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    $lowUtilization = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    $highUtilization = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    $conflicting = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    foreach ([$lowUtilization, $highUtilization, $conflicting] as $resource) {
        ResourceQualification::factory()->create([
            'resource_id' => $resource->id,
            'qualification_id' => $qualification->id,
            'level' => QualificationLevel::Advanced,
        ]);
    }

    TaskAssignment::factory()->create([
        'resource_id' => $highUtilization->id,
        'starts_at' => $task->starts_at,
        'ends_at' => $task->ends_at,
        'allocation_ratio' => 0.7,
    ]);

    TaskAssignment::factory()->create([
        'resource_id' => $conflicting->id,
        'starts_at' => $task->starts_at,
        'ends_at' => $task->ends_at,
        'allocation_ratio' => 0.9,
    ]);

    postJson(route('conflict-resolution'), [
        'current_resource_id' => $currentResource->id,
        'task_id' => $task->id,
        'allocation_ratio' => 0.2,
    ])
        ->assertSuccessful()
        ->assertJsonPath('alternatives.0.id', $lowUtilization->id)
        ->assertJsonPath('alternatives.1.id', $highUtilization->id)
        ->assertJsonMissing(['id' => $conflicting->id]);
});

test('conflict-resolution uses resource type when no task is provided', function (): void {
    $resourceType = ResourceType::factory()->create();
    $otherType = ResourceType::factory()->create();

    $currentResource = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
    ]);

    $sameTypeResource = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
    ]);

    Resource::factory()->create([
        'resource_type_id' => $otherType->id,
    ]);

    postJson(route('conflict-resolution'), [
        'current_resource_id' => $currentResource->id,
        'starts_at' => '2026-03-04',
        'ends_at' => '2026-03-06',
    ])
        ->assertSuccessful()
        ->assertJsonPath('alternatives.0.id', $sameTypeResource->id)
        ->assertJsonCount(1, 'alternatives');
});

test('conflict-resolution requires ManualAssignment write permission', function (): void {
    actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $resource = Resource::factory()->create();

    postJson(route('conflict-resolution'), [
        'current_resource_id' => $resource->id,
        'starts_at' => '2026-03-04',
        'ends_at' => '2026-03-06',
    ])
        ->assertForbidden();
});
