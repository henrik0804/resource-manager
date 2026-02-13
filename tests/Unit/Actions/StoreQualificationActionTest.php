<?php

declare(strict_types=1);

use App\Actions\StoreQualificationAction;
use App\Models\Qualification;
use App\Models\ResourceType;

test('store qualification action creates a qualification', function (): void {
    $resourceType = ResourceType::factory()->create();

    $qualification = app(StoreQualificationAction::class)->handle([
        'name' => 'Rigging',
        'description' => 'Certified for rigging.',
        'resource_type_id' => $resourceType->id,
    ]);

    expect($qualification)->toBeInstanceOf(Qualification::class);
    expect($qualification->resource_type_id)->toBe($resourceType->id);
});

test('store qualification action can create a resource type', function (): void {
    $qualification = app(StoreQualificationAction::class)->handle([
        'name' => 'Safety',
        'resource_type' => [
            'name' => 'Equipment',
            'description' => 'Shared gear.',
        ],
    ]);

    $resourceType = ResourceType::query()->first();

    expect($resourceType)->not->toBeNull();
    expect($qualification->resource_type_id)->toBe($resourceType->id);
});

test('store qualification action rejects conflicting resource type inputs', function (): void {
    $resourceType = ResourceType::factory()->create();

    app(StoreQualificationAction::class)->handle([
        'name' => 'Safety',
        'resource_type_id' => $resourceType->id,
        'resource_type' => [
            'name' => 'Equipment',
        ],
    ]);
})->throws(InvalidArgumentException::class);
