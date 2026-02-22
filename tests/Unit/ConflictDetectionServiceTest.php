<?php

declare(strict_types=1);

use App\Enums\ConflictType;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\TaskAssignment;
use App\Services\ConflictDetectionService;
use Carbon\CarbonImmutable;

test('conflict detection reports double booking and unavailability when parallel capacity is exceeded', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    assert($resource instanceof Resource);

    $existingAssignment = TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-02-12 09:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-02-12 11:00:00'),
        'allocation_ratio' => 0.6,
    ]);

    $absence = ResourceAbsence::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-02-12 11:30:00'),
        'ends_at' => CarbonImmutable::parse('2026-02-12 13:00:00'),
    ]);

    $report = app(ConflictDetectionService::class)->detect(
        resource: $resource,
        startsAt: CarbonImmutable::parse('2026-02-12 10:00:00'),
        endsAt: CarbonImmutable::parse('2026-02-12 12:00:00'),
        allocationRatio: 0.6,
    );

    expect($report->hasConflicts())->toBeTrue();

    expect($report->types())->toEqualCanonicalizing([
        ConflictType::DoubleBooked,
        ConflictType::Unavailable,
    ]);

    $doubleBooked = $report->conflictsFor(ConflictType::DoubleBooked)->first();
    expect($doubleBooked['related_ids'])->toEqualCanonicalizing([$existingAssignment->id]);
    expect($doubleBooked['metrics'])->toHaveKeys(['allocation', 'capacity', 'capacity_unit']);
    expect($doubleBooked['metrics']['allocation'])->toBeGreaterThan($doubleBooked['metrics']['capacity']);

    $unavailable = $report->conflictsFor(ConflictType::Unavailable)->first();
    expect($unavailable['related_ids'])->toEqualCanonicalizing([$absence->id]);
});

test('conflict detection reports overload when a single allocation exceeds capacity', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    assert($resource instanceof Resource);

    $report = app(ConflictDetectionService::class)->detect(
        resource: $resource,
        startsAt: CarbonImmutable::parse('2026-02-12 09:00:00'),
        endsAt: CarbonImmutable::parse('2026-02-12 11:00:00'),
        allocationRatio: 1.4,
    );

    expect($report->hasConflicts())->toBeTrue();

    expect($report->types())->toEqualCanonicalizing([
        ConflictType::Overloaded,
    ]);

    $overloaded = $report->conflictsFor(ConflictType::Overloaded)->first();
    expect($overloaded['metrics'])->toHaveKeys(['allocation', 'capacity', 'capacity_unit']);
    expect($overloaded['metrics']['allocation'])->toBeGreaterThan($overloaded['metrics']['capacity']);
});

test('conflict detection ignores assignments outside the daily time window', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    assert($resource instanceof Resource);

    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-02-12 18:00:00'),
        'ends_at' => CarbonImmutable::parse('2026-02-12 20:00:00'),
        'allocation_ratio' => 2,
    ]);

    $report = app(ConflictDetectionService::class)->detect(
        resource: $resource,
        startsAt: CarbonImmutable::parse('2026-02-12 08:00:00'),
        endsAt: CarbonImmutable::parse('2026-02-12 16:00:00'),
        allocationRatio: 8,
    );

    expect($report->hasConflicts())->toBeFalse();
});
