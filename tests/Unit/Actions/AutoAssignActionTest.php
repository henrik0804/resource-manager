<?php

declare(strict_types=1);

use App\Actions\AutoAssignAction;
use App\Enums\AssignmentSource;
use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use Carbon\CarbonImmutable;

test('auto assign action chooses the lowest utilization resource', function (): void {
    $qualification = Qualification::factory()->create();

    $taskStartsAt = CarbonImmutable::parse('2026-02-10 08:00:00');
    $taskEndsAt = CarbonImmutable::parse('2026-02-12 18:00:00');

    $task = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $busyResource = Resource::factory()->create();
    $freeResource = Resource::factory()->create();

    ResourceQualification::factory()->create([
        'resource_id' => $busyResource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $freeResource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Intermediate,
    ]);

    $busyTask = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-02-11 08:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-02-11 12:00:00'),
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $busyTask->id,
        'resource_id' => $busyResource->id,
        'starts_at' => $busyTask->starts_at,
        'ends_at' => $busyTask->ends_at,
        'allocation_ratio' => 0.5,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect($result['suggestions'])->toBe([]);

    $assignment = TaskAssignment::query()->where('task_id', $task->id)->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->resource_id)->toBe($freeResource->id);
    expect($assignment->assignment_source)->toBe(AssignmentSource::Automated);
});

test('auto assign action returns shift suggestions for higher priority tasks', function (): void {
    $qualification = Qualification::factory()->create();

    $taskStartsAt = CarbonImmutable::parse('2026-02-14 08:00:00');
    $taskEndsAt = CarbonImmutable::parse('2026-02-14 18:00:00');

    $highPriorityTask = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'priority' => 'urgent',
    ]);

    TaskRequirement::factory()->create([
        'task_id' => $highPriorityTask->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    ResourceQualification::factory()->create([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    $lowPriorityTask = Task::factory()->create([
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'priority' => 'low',
    ]);

    TaskAssignment::factory()->create([
        'task_id' => $lowPriorityTask->id,
        'resource_id' => $resource->id,
        'starts_at' => $taskStartsAt,
        'ends_at' => $taskEndsAt,
        'allocation_ratio' => 1,
        'assignment_source' => AssignmentSource::Manual,
    ]);

    $result = app(AutoAssignAction::class)->handle();

    expect($result['assigned'])->toBe(0);
    expect($result['skipped'])->toBe(1);
    expect($result['suggestions'])->toHaveCount(1);
    expect($result['suggestions'][0]['task']['id'])->toBe($highPriorityTask->id);
    expect($result['suggestions'][0]['resources'][0]['blocking_assignments'][0]['task_id'])->toBe($lowPriorityTask->id);
});
