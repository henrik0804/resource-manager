<?php

declare(strict_types=1);

use App\Actions\StoreTaskRequirementAction;
use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Task;
use App\Models\TaskRequirement;
use Carbon\CarbonImmutable;

test('store task requirement action creates a requirement', function (): void {
    $task = Task::factory()->create();
    $qualification = Qualification::factory()->create();

    $requirement = app(StoreTaskRequirementAction::class)->handle([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate,
    ]);

    expect($requirement)->toBeInstanceOf(TaskRequirement::class);
    expect($requirement->task_id)->toBe($task->id);
    expect($requirement->qualification_id)->toBe($qualification->id);
    expect($requirement->required_level)->toBe(QualificationLevel::Intermediate);
});

test('store task requirement action can create dependencies', function (): void {
    $startsAt = CarbonImmutable::parse('2026-02-12 08:00:00');
    $endsAt = $startsAt->addHours(4);

    $requirement = app(StoreTaskRequirementAction::class)->handle([
        'task' => [
            'title' => 'Load-in',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'effort_value' => 4,
            'effort_unit' => 'hours',
            'priority' => 'high',
            'status' => 'planned',
        ],
        'qualification' => [
            'name' => 'Safety',
            'description' => 'Safety training required.',
        ],
    ]);

    $task = Task::query()->first();
    $qualification = Qualification::query()->first();

    expect($task)->not->toBeNull();
    expect($qualification)->not->toBeNull();
    expect($requirement->task_id)->toBe($task->id);
    expect($requirement->qualification_id)->toBe($qualification->id);
});

test('store task requirement action requires task and qualification', function (): void {
    $task = Task::factory()->create();

    app(StoreTaskRequirementAction::class)->handle([
        'task_id' => $task->id,
    ]);
})->throws(InvalidArgumentException::class);

test('store task requirement action rejects conflicting task inputs', function (): void {
    $task = Task::factory()->create();
    $qualification = Qualification::factory()->create();
    $startsAt = CarbonImmutable::parse('2026-02-12 09:30:00');
    $endsAt = $startsAt->addHours(3);

    app(StoreTaskRequirementAction::class)->handle([
        'task_id' => $task->id,
        'task' => [
            'title' => 'Load-out',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'effort_value' => 3,
            'effort_unit' => 'hours',
            'priority' => 'medium',
            'status' => 'planned',
        ],
        'qualification_id' => $qualification->id,
    ]);
})->throws(InvalidArgumentException::class);

test('store task requirement action rejects conflicting qualification inputs', function (): void {
    $task = Task::factory()->create();
    $qualification = Qualification::factory()->create();

    app(StoreTaskRequirementAction::class)->handle([
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'qualification' => [
            'name' => 'Safety',
        ],
    ]);
})->throws(InvalidArgumentException::class);
