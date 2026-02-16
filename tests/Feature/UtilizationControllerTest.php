<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\TaskAssignment;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

test('guests are redirected from the utilization page', function (): void {
    get(route('utilization'))->assertRedirect(route('login'));
});

test('users without UtilizationView permission are forbidden', function (): void {
    actingAsUserWithPermissions([
        'read' => [AccessSection::ResourceManagement],
        'write' => [],
    ]);

    get(route('utilization'))->assertForbidden();
});

describe('authorized users', function (): void {
    beforeEach(function (): void {
        actingAsUserWithPermissions([
            'read' => [AccessSection::UtilizationView],
        ]);
    });

    test('can view the utilization page', function (): void {
        Resource::factory()->count(3)->create();

        get(route('utilization'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('utilization/Index')
                ->has('resources', 3)
                ->has('period')
                ->where('period.granularity', 'week')
            );
    });

    test('utilization page returns resource data with expected structure', function (): void {
        $resource = Resource::factory()->create([
            'capacity_value' => 8,
            'capacity_unit' => 'hours_per_day',
        ]);

        TaskAssignment::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::now()->addDays(1),
            'ends_at' => CarbonImmutable::now()->addDays(5),
            'allocation_ratio' => 4,
        ]);

        get(route('utilization', [
            'start' => CarbonImmutable::now()->format('Y-m-d'),
            'end' => CarbonImmutable::now()->addMonth()->format('Y-m-d'),
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('utilization/Index')
                ->has('resources', fn (Assert $resources) => $resources
                    ->has(Resource::count())
                    ->first(fn (Assert $resource) => $resource
                        ->has('id')
                        ->has('name')
                        ->has('resource_type')
                        ->has('capacity_per_day')
                        ->has('capacity_unit')
                        ->has('summary.total_days')
                        ->has('summary.total_capacity')
                        ->has('summary.total_allocated')
                        ->has('summary.total_absent')
                        ->has('summary.available_capacity')
                        ->has('summary.utilization_percentage')
                        ->has('buckets')
                    )
                )
            );
    });

    test('utilization respects date range query parameters', function (): void {
        $resource = Resource::factory()->create();

        TaskAssignment::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::parse('2026-06-01'),
            'ends_at' => CarbonImmutable::parse('2026-06-10'),
            'allocation_ratio' => 1,
        ]);

        // Request a range that does NOT include the assignment
        get(route('utilization', [
            'start' => '2026-01-01',
            'end' => '2026-02-01',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('utilization/Index')
                ->has('resources', fn (Assert $resources) => $resources
                    ->has(Resource::count())
                    ->first(fn (Assert $resource) => $resource
                        ->where('summary.total_allocated', 0)
                        ->where('summary.utilization_percentage', 0)
                        ->etc()
                    )
                )
            );
    });

    test('utilization accepts granularity parameter', function (): void {
        get(route('utilization', ['granularity' => 'day']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('utilization/Index')
                ->where('period.granularity', 'day')
            );
    });

    test('utilization includes absence impact on available capacity', function (): void {
        $resource = Resource::factory()->create([
            'capacity_value' => 8,
            'capacity_unit' => 'hours_per_day',
        ]);

        ResourceAbsence::factory()->create([
            'resource_id' => $resource->id,
            'starts_at' => CarbonImmutable::parse('2026-03-03'),
            'ends_at' => CarbonImmutable::parse('2026-03-05'),
        ]);

        get(route('utilization', [
            'start' => '2026-03-01',
            'end' => '2026-03-08',
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('utilization/Index')
                ->has('resources', fn (Assert $resources) => $resources
                    ->has(Resource::count())
                    ->first(fn (Assert $resource) => $resource
                        ->where('summary.total_absent', 16) // 2 days × 8 hours
                        ->etc()
                    )
                )
            );
    });
});
