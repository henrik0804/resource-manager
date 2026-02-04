<?php

declare(strict_types=1);

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use App\Models\User;

test('core model relationships are wired', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $resourceType = ResourceType::factory()->create();
    $resource = Resource::factory()->create([
        'resource_type_id' => $resourceType->id,
        'user_id' => $user->id,
    ]);

    $qualification = Qualification::factory()->create([
        'resource_type_id' => $resourceType->id,
    ]);

    $resourceQualification = ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
    ]);

    $task = Task::factory()->create();
    $taskRequirement = TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
    ]);

    $taskAssignment = TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'resource_id' => $resource->id,
    ]);

    $absence = ResourceAbsence::factory()->create([
        'resource_id' => $resource->id,
    ]);

    expect($user->role->is($role))->toBeTrue();
    expect($user->resource->is($resource))->toBeTrue();
    expect($resource->resourceType->is($resourceType))->toBeTrue();
    expect($resource->resourceQualifications)->toHaveCount(1);
    expect($resourceQualification->qualification->is($qualification))->toBeTrue();
    expect($task->requirements)->toHaveCount(1);
    expect($task->assignments)->toHaveCount(1);
    expect($taskRequirement->task->is($task))->toBeTrue();
    expect($taskAssignment->resource->is($resource))->toBeTrue();
    expect($absence->resource->is($resource))->toBeTrue();
});

test('qualification levels are cast to enums', function (): void {
    $resourceType = ResourceType::factory()->create();
    $resource = Resource::factory()->create(['resource_type_id' => $resourceType->id]);
    $qualification = Qualification::factory()->create(['resource_type_id' => $resourceType->id]);

    $resourceQualification = ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    $task = Task::factory()->create();
    $taskRequirement = TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    expect($resourceQualification->refresh()->level)->toBe(QualificationLevel::Advanced);
    expect($taskRequirement->refresh()->required_level)->toBe(QualificationLevel::Intermediate);
});
