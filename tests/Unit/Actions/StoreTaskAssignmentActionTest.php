<?php

declare(strict_types=1);

use App\Actions\StoreTaskAssignmentAction;
use App\Enums\AssignmentSource;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;

test('store task assignment action creates an assignment', function (): void {
    $task = Task::factory()->create();
    $resource = Resource::factory()->create();
    $startsAt = CarbonImmutable::parse('2026-02-12 12:00:00');

    $assignment = app(StoreTaskAssignmentAction::class)->handle([
        'task_id' => $task->id,
        'resource_id' => $resource->id,
        'starts_at' => $startsAt,
        'allocation_ratio' => 0.5,
        'assignment_source' => 'manual',
        'assignee_status' => 'accepted',
    ]);

    expect($assignment)->toBeInstanceOf(TaskAssignment::class);
    expect($assignment->task_id)->toBe($task->id);
    expect($assignment->resource_id)->toBe($resource->id);
    expect($assignment->assignment_source)->toBe(AssignmentSource::Manual);
});

test('store task assignment action can create dependencies', function (): void {
    $startsAt = CarbonImmutable::parse('2026-02-12 07:00:00');
    $endsAt = $startsAt->addHours(2);

    $assignment = app(StoreTaskAssignmentAction::class)->handle([
        'task' => [
            'title' => 'Soundcheck',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'effort_value' => 2,
            'effort_unit' => 'hours',
            'priority' => 'medium',
            'status' => 'planned',
        ],
        'resource' => [
            'name' => 'Stage A',
            'resource_type' => [
                'name' => 'Venue',
            ],
        ],
        'assignment_source' => 'automated',
    ]);

    $task = Task::query()->first();
    $resource = Resource::query()->first();

    expect($task)->not->toBeNull();
    expect($resource)->not->toBeNull();
    expect($assignment->task_id)->toBe($task->id);
    expect($assignment->resource_id)->toBe($resource->id);
});

test('store task assignment action requires task and resource', function (): void {
    $resource = Resource::factory()->create();

    app(StoreTaskAssignmentAction::class)->handle([
        'resource_id' => $resource->id,
        'assignment_source' => 'manual',
    ]);
})->throws(InvalidArgumentException::class);

test('store task assignment action rejects conflicting task inputs', function (): void {
    $task = Task::factory()->create();
    $resource = Resource::factory()->create();
    $startsAt = CarbonImmutable::parse('2026-02-12 06:00:00');
    $endsAt = $startsAt->addHours(2);

    app(StoreTaskAssignmentAction::class)->handle([
        'task_id' => $task->id,
        'task' => [
            'title' => 'Soundcheck',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'effort_value' => 2,
            'effort_unit' => 'hours',
            'priority' => 'medium',
            'status' => 'planned',
        ],
        'resource_id' => $resource->id,
        'assignment_source' => 'manual',
    ]);
})->throws(InvalidArgumentException::class);

test('store task assignment action rejects conflicting resource inputs', function (): void {
    $task = Task::factory()->create();
    $resource = Resource::factory()->create();

    app(StoreTaskAssignmentAction::class)->handle([
        'task_id' => $task->id,
        'resource_id' => $resource->id,
        'resource' => [
            'name' => 'Stage A',
            'resource_type' => [
                'name' => 'Venue',
            ],
        ],
        'assignment_source' => 'manual',
    ]);
})->throws(InvalidArgumentException::class);
