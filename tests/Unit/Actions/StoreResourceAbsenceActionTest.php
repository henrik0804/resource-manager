<?php

declare(strict_types=1);

use App\Actions\StoreResourceAbsenceAction;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use Carbon\CarbonImmutable;

test('store resource absence action creates an absence', function (): void {
    $resource = Resource::factory()->create();
    $startsAt = CarbonImmutable::parse('2026-02-12 09:00:00');
    $endsAt = $startsAt->addHours(8);

    $absence = app(StoreResourceAbsenceAction::class)->handle([
        'resource_id' => $resource->id,
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
        'recurrence_rule' => 'FREQ=DAILY;COUNT=2',
    ]);

    expect($absence)->toBeInstanceOf(ResourceAbsence::class);
    expect($absence->resource_id)->toBe($resource->id);
    expect($absence->recurrence_rule)->toBe('FREQ=DAILY;COUNT=2');
});

test('store resource absence action can create dependencies', function (): void {
    $startsAt = CarbonImmutable::parse('2026-02-12 09:00:00');
    $endsAt = $startsAt->addHours(4);

    $absence = app(StoreResourceAbsenceAction::class)->handle([
        'resource' => [
            'name' => 'Rig C',
            'resource_type' => [
                'name' => 'Vehicle',
            ],
        ],
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);

    $resource = Resource::query()->first();

    expect($resource)->not->toBeNull();
    expect($absence->resource_id)->toBe($resource->id);
});

test('store resource absence action requires a resource', function (): void {
    $startsAt = CarbonImmutable::parse('2026-02-12 09:00:00');
    $endsAt = $startsAt->addHours(4);

    app(StoreResourceAbsenceAction::class)->handle([
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
})->throws(InvalidArgumentException::class);

test('store resource absence action rejects conflicting resource inputs', function (): void {
    $resource = Resource::factory()->create();
    $startsAt = CarbonImmutable::parse('2026-02-12 12:00:00');
    $endsAt = $startsAt->addHours(2);

    app(StoreResourceAbsenceAction::class)->handle([
        'resource_id' => $resource->id,
        'resource' => [
            'name' => 'Rig D',
            'resource_type' => [
                'name' => 'Equipment',
            ],
        ],
        'starts_at' => $startsAt,
        'ends_at' => $endsAt,
    ]);
})->throws(InvalidArgumentException::class);
