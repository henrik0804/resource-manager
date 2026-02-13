<?php

declare(strict_types=1);

use App\Actions\StoreUserAction;
use App\Models\Role;
use App\Models\User;

test('store user action creates a user with a role id', function (): void {
    $role = Role::factory()->create();

    $user = app(StoreUserAction::class)->handle([
        'name' => 'Sam Rivera',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'secret-password',
        'role_id' => $role->id,
    ]);

    expect($user)->toBeInstanceOf(User::class);
    expect($user->role->is($role))->toBeTrue();
});

test('store user action can create a role', function (): void {
    $user = app(StoreUserAction::class)->handle([
        'name' => 'Jess Park',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'secret-password',
        'role' => [
            'name' => 'Scheduler',
            'description' => 'Plans work.',
        ],
    ]);

    $role = Role::query()->first();

    expect($role)->not->toBeNull();
    expect($user->role->is($role))->toBeTrue();
});

test('store user action requires a role', function (): void {
    app(StoreUserAction::class)->handle([
        'name' => 'Sam Rivera',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'secret-password',
    ]);
})->throws(InvalidArgumentException::class);

test('store user action rejects conflicting role inputs', function (): void {
    $role = Role::factory()->create();

    app(StoreUserAction::class)->handle([
        'name' => 'Jess Park',
        'email' => fake()->unique()->safeEmail(),
        'password' => 'secret-password',
        'role_id' => $role->id,
        'role' => [
            'name' => 'Scheduler',
        ],
    ]);
})->throws(InvalidArgumentException::class);
