<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\Task;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('check-conflicts returns no conflicts for a free resource', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-05',
        'allocation_ratio' => 0.5,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => false,
            'conflicts' => [],
        ]);
});

test('check-conflicts detects double booking', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-01'),
        'ends_at' => CarbonImmutable::parse('2026-03-05'),
        'allocation_ratio' => 0.6,
    ]);

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'starts_at' => '2026-03-03',
        'ends_at' => '2026-03-07',
        'allocation_ratio' => 0.6,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => true,
        ])
        ->assertJsonPath('conflicts.double_booked.label', 'Doppelbuchung');
});

test('check-conflicts detects overload', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'starts_at' => '2026-03-02',
        'ends_at' => '2026-03-04',
        'allocation_ratio' => 1.4,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => true,
        ])
        ->assertJsonPath('conflicts.overloaded.label', 'Überlastung');
});

test('check-conflicts detects unavailability', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    ResourceAbsence::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-02'),
        'ends_at' => CarbonImmutable::parse('2026-03-04'),
    ]);

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-05',
        'allocation_ratio' => 0.5,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => true,
        ])
        ->assertJsonPath('conflicts.unavailable.label', 'Nicht verfügbar');
});

test('check-conflicts excludes the current assignment during edits', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    $existingAssignment = TaskAssignment::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-01'),
        'ends_at' => CarbonImmutable::parse('2026-03-05'),
        'allocation_ratio' => 0.5,
    ]);

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-05',
        'allocation_ratio' => 0.5,
        'exclude_assignment_id' => $existingAssignment->id,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => false,
            'conflicts' => [],
        ]);
});

test('check-conflicts falls back to task dates when assignment dates are missing', function (): void {
    $resource = Resource::factory()->create([
        'capacity_value' => 1,
        'capacity_unit' => 'hours_per_day',
    ]);

    $task = Task::factory()->create([
        'starts_at' => CarbonImmutable::parse('2026-03-01'),
        'ends_at' => CarbonImmutable::parse('2026-03-05'),
    ]);

    ResourceAbsence::factory()->create([
        'resource_id' => $resource->id,
        'starts_at' => CarbonImmutable::parse('2026-03-02'),
        'ends_at' => CarbonImmutable::parse('2026-03-04'),
    ]);

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'task_id' => $task->id,
        'allocation_ratio' => 0.5,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => true,
        ]);
});

test('check-conflicts returns empty when no dates are resolvable', function (): void {
    $resource = Resource::factory()->create();

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
    ])
        ->assertSuccessful()
        ->assertJson([
            'has_conflicts' => false,
            'conflicts' => [],
        ]);
});

test('check-conflicts requires resource_id', function (): void {
    postJson(route('check-conflicts'), [
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-05',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['resource_id']);
});

test('check-conflicts requires ManualAssignment write permission', function (): void {
    actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $resource = Resource::factory()->create();

    postJson(route('check-conflicts'), [
        'resource_id' => $resource->id,
        'starts_at' => '2026-03-01',
        'ends_at' => '2026-03-05',
    ])
        ->assertForbidden();
});
