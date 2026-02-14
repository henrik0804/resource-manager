<?php

declare(strict_types=1);

use App\Enums\ConflictType;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\TaskAssignment;
use App\Services\ConflictDetectionService;
use Carbon\CarbonImmutable;

test('conflict detection reports double booking, overload, and unavailability', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

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
        ConflictType::Overloaded,
        ConflictType::Unavailable,
    ]);

    $doubleBooked = $report->conflictsFor(ConflictType::DoubleBooked)->first();
    expect($doubleBooked['related_ids'])->toEqualCanonicalizing([$existingAssignment->id]);

    $overloaded = $report->conflictsFor(ConflictType::Overloaded)->first();
    expect($overloaded['metrics'])->toHaveKeys(['total_allocation', 'capacity', 'capacity_unit']);
    expect($overloaded['metrics']['total_allocation'])->toBeGreaterThan($overloaded['metrics']['capacity']);

    $unavailable = $report->conflictsFor(ConflictType::Unavailable)->first();
    expect($unavailable['related_ids'])->toEqualCanonicalizing([$absence->id]);
});
