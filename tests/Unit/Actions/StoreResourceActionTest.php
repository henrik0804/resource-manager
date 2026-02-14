<?php

declare(strict_types=1);

use App\Actions\StoreResourceAction;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Role;
use App\Models\User;

test('store resource action creates a resource with ids', function (): void {
    $resourceType = ResourceType::factory()->create();
    $user = User::factory()->create();

    $resource = app(StoreResourceAction::class)->handle([
        'name' => 'Studio Alpha',
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 8,
        'capacity_unit' => 'hours_per_day',
        'user_id' => $user->id,
    ]);

    expect($resource)->toBeInstanceOf(Resource::class);
    expect($resource->resource_type_id)->toBe($resourceType->id);
    expect($resource->user_id)->toBe($user->id);
});

test('store resource action can create dependencies', function (): void {
    $resource = app(StoreResourceAction::class)->handle([
        'name' => 'Rig A',
        'resource_type' => [
            'name' => 'Crew',
            'description' => 'Production crew pool.',
        ],
        'user' => [
            'name' => 'Taylor Quinn',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret-password',
            'role' => [
                'name' => 'Coordinator',
                'description' => 'Owns the schedule.',
            ],
        ],
    ]);

    $resourceType = ResourceType::query()->first();
    $role = Role::query()->first();
    $user = User::query()->first();

    expect($resourceType)->not->toBeNull();
    expect($role)->not->toBeNull();
    expect($user)->not->toBeNull();
    expect($resource->resource_type_id)->toBe($resourceType->id);
    expect($resource->user_id)->toBe($user->id);
});

test('store resource action requires a resource type', function (): void {
    app(StoreResourceAction::class)->handle([
        'name' => 'Rig D',
    ]);
})->throws(InvalidArgumentException::class);

test('store resource action rejects conflicting resource type inputs', function (): void {
    $resourceType = ResourceType::factory()->create();

    app(StoreResourceAction::class)->handle([
        'name' => 'Rig E',
        'resource_type_id' => $resourceType->id,
        'resource_type' => [
            'name' => 'Crew',
        ],
    ]);
})->throws(InvalidArgumentException::class);

test('store resource action rejects conflicting user inputs', function (): void {
    $resourceType = ResourceType::factory()->create();
    $user = User::factory()->create();

    app(StoreResourceAction::class)->handle([
        'name' => 'Rig F',
        'resource_type_id' => $resourceType->id,
        'user_id' => $user->id,
        'user' => [
            'name' => 'Jordan Blake',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret-password',
            'role' => [
                'name' => 'Coordinator',
            ],
        ],
    ]);
})->throws(InvalidArgumentException::class);
