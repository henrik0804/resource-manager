<?php

declare(strict_types=1);

use App\Actions\StoreResourceQualificationAction;
use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;

test('store resource qualification action creates a resource qualification', function (): void {
    $resource = Resource::factory()->create();
    $qualification = Qualification::factory()->create();

    $resourceQualification = app(StoreResourceQualificationAction::class)->handle([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced,
    ]);

    expect($resourceQualification)->toBeInstanceOf(ResourceQualification::class);
    expect($resourceQualification->resource_id)->toBe($resource->id);
    expect($resourceQualification->qualification_id)->toBe($qualification->id);
    expect($resourceQualification->level)->toBe(QualificationLevel::Advanced);
});

test('store resource qualification action can create dependencies', function (): void {
    $resourceQualification = app(StoreResourceQualificationAction::class)->handle([
        'resource' => [
            'name' => 'Rig B',
            'resource_type' => [
                'name' => 'Equipment',
            ],
        ],
        'qualification' => [
            'name' => 'Operator',
            'description' => 'Certified operator.',
        ],
    ]);

    $resource = Resource::query()->first();
    $qualification = Qualification::query()->first();

    expect($resource)->not->toBeNull();
    expect($qualification)->not->toBeNull();
    expect($resourceQualification->resource_id)->toBe($resource->id);
    expect($resourceQualification->qualification_id)->toBe($qualification->id);
});

test('store resource qualification action requires a qualification', function (): void {
    $resource = Resource::factory()->create();

    app(StoreResourceQualificationAction::class)->handle([
        'resource_id' => $resource->id,
    ]);
})->throws(InvalidArgumentException::class);

test('store resource qualification action rejects conflicting resource inputs', function (): void {
    $resource = Resource::factory()->create();
    $qualification = Qualification::factory()->create();

    app(StoreResourceQualificationAction::class)->handle([
        'resource_id' => $resource->id,
        'resource' => [
            'name' => 'Rig E',
            'resource_type' => [
                'name' => 'Vehicle',
            ],
        ],
        'qualification_id' => $qualification->id,
    ]);
})->throws(InvalidArgumentException::class);

test('store resource qualification action rejects conflicting qualification inputs', function (): void {
    $resource = Resource::factory()->create();
    $qualification = Qualification::factory()->create();

    app(StoreResourceQualificationAction::class)->handle([
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'qualification' => [
            'name' => 'Operator',
        ],
    ]);
})->throws(InvalidArgumentException::class);
