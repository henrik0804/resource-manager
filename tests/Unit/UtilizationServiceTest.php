<?php

declare(strict_types=1);

use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Services\UtilizationService;
use Carbon\CarbonImmutable;

test('returns empty resources list when range is invalid', function (): void {
    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-10'),
        CarbonImmutable::parse('2026-03-01'),
    );

    expect($result['resources'])->toBeEmpty();
    expect($result['period']['granularity'])->toBe('week');
});

test('calculates zero utilization for a resource with no assignments', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-08'),
    );

    expect($result['resources'])->toHaveCount(1);

    $resourceData = $result['resources'][0];
    expect($resourceData['id'])->toBe($resource->id);
    expect($resourceData['name'])->toBe($resource->name);
    expect($resourceData['capacity_per_day'])->toBe(8.0);
    expect($resourceData['capacity_unit'])->toBe('hours_per_day');
    expect($resourceData['summary']['utilization_percentage'])->toBe(0.0);
    expect($resourceData['summary']['total_capacity'])->toBe(56.0); // 8 * 7 days
    expect($resourceData['summary']['total_allocated'])->toBe(0.0);
});

test('calculates utilization for a resource with a single assignment', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-01'),
        'ends_at' => CarbonImmutable::parse('2026-03-08'),
        'allocation_ratio' => 4,
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-08'),
    );

    $summary = $result['resources'][0]['summary'];
    // 7 days × capacity 8 = 56 total capacity
    // 7 days × allocation 4 = 28 allocated
    // 28 / 56 = 50%
    expect($summary['total_capacity'])->toBe(56.0);
    expect($summary['total_allocated'])->toBe(28.0);
    expect($summary['utilization_percentage'])->toBe(50.0);
});

test('detects overloaded resources exceeding 100% utilization', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    // Two overlapping assignments each using 0.8 of a single slot
    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-03'),
        'ends_at' => CarbonImmutable::parse('2026-03-05'),
        'allocation_ratio' => 0.8,
    ]);

    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-03'),
        'ends_at' => CarbonImmutable::parse('2026-03-05'),
        'allocation_ratio' => 0.8,
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-03'),
        CarbonImmutable::parse('2026-03-05'),
    );

    $summary = $result['resources'][0]['summary'];
    // capacity = 1 × 2 days = 2
    // allocated = (0.8 + 0.8) × 2 days = 3.2
    // utilization = 3.2 / 2 = 160%
    expect($summary['utilization_percentage'])->toBeGreaterThan(100);
});

test('accounts for absences by reducing available capacity', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-01'),
        'ends_at' => CarbonImmutable::parse('2026-03-08'),
        'allocation_ratio' => 4,
    ]);

    ResourceAbsence::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-03'),
        'ends_at' => CarbonImmutable::parse('2026-03-05'),
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-08'),
    );

    $summary = $result['resources'][0]['summary'];
    // capacity = 8 × 7 = 56
    // absent = 8 × 2 = 16
    // available = 56 - 16 = 40
    // allocated = 4 × 7 = 28
    // utilization = 28 / 40 = 70%
    expect($summary['total_absent'])->toBe(16.0);
    expect($summary['available_capacity'])->toBe(40.0);
    expect($summary['utilization_percentage'])->toBe(70.0);
});

test('falls back to task dates when assignment has no dates', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'slots',
    ]);

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-03-02'),
        'ends_at' => CarbonImmutable::parse('2026-03-04'),
    ]);

    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'task_id' => $task->id,
        'starts_at' => null,
        'ends_at' => null,
        'allocation_ratio' => 0.5,
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-08'),
    );

    $summary = $result['resources'][0]['summary'];
    // Assignment inherits task dates: Mar 2-4 = 2 days overlap
    // allocated = 0.5 × 2 = 1.0
    expect($summary['total_allocated'])->toBe(1.0);
    expect($summary['utilization_percentage'])->toBeGreaterThan(0);
});

test('builds correct time buckets for weekly granularity', function (): void {
    Resource::factory()->create();

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-02'),
        CarbonImmutable::parse('2026-03-16'),
        'week',
    );

    $buckets = $result['resources'][0]['buckets'];
    expect($buckets)->toHaveCount(2);
    expect($buckets[0]['start'])->toBe('2026-03-02');
    expect($buckets[0]['end'])->toBe('2026-03-09');
    expect($buckets[1]['start'])->toBe('2026-03-09');
    expect($buckets[1]['end'])->toBe('2026-03-16');
});

test('builds correct time buckets for daily granularity', function (): void {
    Resource::factory()->create();

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-04'),
        'day',
    );

    $buckets = $result['resources'][0]['buckets'];
    expect($buckets)->toHaveCount(3);
    expect($buckets[0]['start'])->toBe('2026-03-01');
    expect($buckets[0]['end'])->toBe('2026-03-02');
});

test('defaults capacity to 1.0 for resources without capacity set', function (): void {
    Resource::factory()->create([
        'capacity_value' => null,
        'capacity_unit' => null,
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-01'),
        CarbonImmutable::parse('2026-03-02'),
    );

    expect($result['resources'][0]['capacity_per_day'])->toBe(1.0);
});

test('only counts assignment overlap within the requested range', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
    ]);

    // Assignment spans Mar 1-10 but we only query Mar 5-8
    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-01'),
        'ends_at' => CarbonImmutable::parse('2026-03-10'),
        'allocation_ratio' => 4,
    ]);

    $service = app(UtilizationService::class);

    $result = $service->calculate(
        CarbonImmutable::parse('2026-03-05'),
        CarbonImmutable::parse('2026-03-08'),
    );

    $summary = $result['resources'][0]['summary'];
    // Range is 3 days, capacity = 8 × 3 = 24
    // Assignment overlaps entire range: 4 × 3 = 12
    // utilization = 12 / 24 = 50%
    expect($summary['total_days'])->toBe(3);
    expect($summary['total_capacity'])->toBe(24.0);
    expect($summary['total_allocated'])->toBe(12.0);
    expect($summary['utilization_percentage'])->toBe(50.0);
});
