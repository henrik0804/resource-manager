<?php

declare(strict_types=1);

use App\Actions\StoreTaskAction;
use App\Enums\EffortUnit;
use App\Models\Task;
use Carbon\CarbonImmutable;

test('store task action creates a task', function (): void {
    $startsAt = CarbonImmutable::parse('2026-02-12 10:00:00');
    $endsAt = $startsAt->addHours(6);

    $task = app(StoreTaskAction::class)->handle([
        'title' => 'Crew setup',
        'description' => 'Prepare the stage and tools.',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'effort_value' => 6,
        'effort_unit' => 'hours',
        'priority' => 'high',
        'status' => 'planned',
    ]);

    expect($task)->toBeInstanceOf(Task::class);
    expect($task->title)->toBe('Crew setup');
    expect($task->effort_unit)->toBe(EffortUnit::Hours);
});

test('store task action allows null descriptions', function (): void {
    $startsAt = CarbonImmutable::parse('2026-02-12 13:00:00');
    $endsAt = $startsAt->addHours(3);

    $task = app(StoreTaskAction::class)->handle([
        'title' => 'Load-in',
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'effort_value' => 3,
        'effort_unit' => 'hours',
        'priority' => 'medium',
        'status' => 'planned',
    ]);

    expect($task->refresh()->description)->toBeNull();
});
