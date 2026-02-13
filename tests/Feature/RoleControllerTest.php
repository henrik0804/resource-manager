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
