<?php

declare(strict_types=1);

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use App\Services\ConflictResolutionService;
use Carbon\CarbonImmutable;

test('conflict resolution suggests qualified, conflict-free alternatives by utilization', function (): void {
    $resourceType = ResourceType::factory()->create();
    $qualification = Qualification::factory()->create([
        'resource_type_id' => $resourceType->id,
    ]);

    $startsAt = CarbonImmutable::parse('2026-02-20 08:00:00');
    $endsAt = CarbonImmutable::parse('2026-02-21 18:00:00');

    $task = Task::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
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

    assert($currentResource instanceof Resource);

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

    ResourceQualification::factory()->create([
        'resource_id' => $lowUtilization->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $highUtilization->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $conflicting->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    $busyTask = Task::factory()->create([
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $busyTask->id,
        'resource_id' => $highUtilization->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'allocation_ratio' => 0.8,
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $busyTask->id,
        'resource_id' => $conflicting->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'allocation_ratio' => 0.9,
    ]);

    $service = app(ConflictResolutionService::class);

    $alternatives = $service->alternatives(
        currentResource: $currentResource,
        startsAt: $startsAt,
        endsAt: $endsAt,
        task: $task,
        allocationRatio: 0.2,
    );

    expect($alternatives)->toHaveCount(2);
    expect($alternatives->pluck('id')->all())->toBe([
        $lowUtilization->id,
        $highUtilization->id,
    ]);
});

test('conflict resolution falls back to resource type when no task is provided', function (): void {
    $resourceType = ResourceType::factory()->create();
    $otherType = ResourceType::factory()->create();

    $currentResource = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    assert($currentResource instanceof Resource);

    $sameTypeResource = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    Resource::factory()->create([
        'resource_type_id' => $otherType->id,
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    $startsAt = CarbonImmutable::parse('2026-02-22 08:00:00');
    $endsAt = CarbonImmutable::parse('2026-02-22 18:00:00');

    $service = app(ConflictResolutionService::class);

    $alternatives = $service->alternatives(
        currentResource: $currentResource,
        startsAt: $startsAt,
        endsAt: $endsAt,
    );

    expect($alternatives)->toHaveCount(1);
    expect($alternatives->pluck('id')->all())->toBe([
        $sameTypeResource->id,
    ]);
});
