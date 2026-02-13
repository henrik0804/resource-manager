<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('roles can be managed', function (): void {
    $backUrl = '/dashboard';

    $storeResponse = from($backUrl)->post(route('roles.store'), [
        'name' => 'Coordinator',
        'description' => 'Coordinates schedules',
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Role created.');
    $role = Role::query()->where('name', 'Coordinator')->first();

    expect($role)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('roles.update', $role), [
        'description' => 'Updated responsibilities',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Role updated.');
    assertDatabaseHas('roles', [
        'id' => $role->id,
        'description' => 'Updated responsibilities',
    ]);

    $deleteResponse = from($backUrl)->delete(route('roles.destroy', $role));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Role deleted.');
    assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('deleting role with users returns has_dependents status', function (): void {
    $backUrl = '/dashboard';
    $role = Role::factory()->create();
    User::factory()->create(['role_id' => $role->id]);

    $deleteResponse = from($backUrl)->delete(route('roles.destroy', $role));

    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('status', 'has_dependents');
    assertDatabaseHas('roles', ['id' => $role->id]);
});

test('deleting role with users and confirmation cascades', function (): void {
    $backUrl = '/dashboard';
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    $deleteResponse = from($backUrl)->delete(route('roles.destroy', $role), [
        'confirm_dependency_deletion' => true,
    ]);

    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Role deleted.');
    assertDatabaseMissing('roles', ['id' => $role->id]);
    assertDatabaseMissing('users', ['id' => $user->id]);
});

test('users without role permissions cannot access roles index', function (): void {
    $user = User::factory()->create();

    actingAs($user)->get(route('roles.index'))
        ->assertForbidden();
});
