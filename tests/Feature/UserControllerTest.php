<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('users can be managed', function (): void {
    $backUrl = '/dashboard';
    $role = Role::factory()->create();

    $storeResponse = from($backUrl)->post(route('users.store'), [
        'name' => 'Alex Manager',
        'email' => 'alex.manager@example.com',
        'password' => 'password123',
        'role_id' => $role->id,
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'User created.');
    $managedUser = User::query()->where('email', 'alex.manager@example.com')->first();

    expect($managedUser)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('users.update', $managedUser), [
        'name' => 'Alex Manager Updated',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'User updated.');
    assertDatabaseHas('users', [
        'id' => $managedUser->id,
        'name' => 'Alex Manager Updated',
    ]);

    $deleteResponse = from($backUrl)->delete(route('users.destroy', $managedUser));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'User deleted.');
    assertDatabaseMissing('users', ['id' => $managedUser->id]);
});
