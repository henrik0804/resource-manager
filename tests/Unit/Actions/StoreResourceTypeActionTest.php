<?php

declare(strict_types=1);

use App\Actions\StoreResourceTypeAction;
use App\Models\ResourceType;

test('store resource type action creates a resource type', function (): void {
    $resourceType = app(StoreResourceTypeAction::class)->handle([
        'name' => 'Equipment',
        'description' => 'Shared gear.',
    ]);

    expect($resourceType)->toBeInstanceOf(ResourceType::class);
    expect($resourceType->name)->toBe('Equipment');
    expect($resourceType->description)->toBe('Shared gear.');
    expect(ResourceType::query()->count())->toBe(1);
});

test('store resource type action allows null descriptions', function (): void {
    $resourceType = app(StoreResourceTypeAction::class)->handle([
        'name' => 'Room',
    ]);

    expect($resourceType->refresh()->description)->toBeNull();
});
