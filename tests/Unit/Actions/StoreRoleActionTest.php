<?php

declare(strict_types=1);

use App\Actions\StoreRoleAction;
use App\Models\Role;

test('store role action creates a role', function (): void {
    $role = app(StoreRoleAction::class)->handle([
        'name' => 'Coordinator',
        'description' => 'Coordinates the schedule.',
    ]);

    expect($role)->toBeInstanceOf(Role::class);
    expect($role->name)->toBe('Coordinator');
    expect($role->description)->toBe('Coordinates the schedule.');
    expect(Role::query()->count())->toBe(1);
});

test('store role action allows null descriptions', function (): void {
    $role = app(StoreRoleAction::class)->handle([
        'name' => 'Analyst',
    ]);

    expect($role->refresh()->description)->toBeNull();
});
