<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Permission;
use App\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('permissions can be managed', function (): void {
    $backUrl = '/dashboard';
    $role = Role::factory()->create();

    $storeResponse = from($backUrl)->post(route('permissions.store'), [
        'role_id' => $role->id,
        'section' => AccessSection::ResourceManagement->value,
        'can_read' => true,
        'can_write' => false,
        'can_write_owned' => false,
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Permission created.');
    $permission = Permission::query()->where('role_id', $role->id)
        ->where('section', AccessSection::ResourceManagement->value)
        ->first();

    expect($permission)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('permissions.update', $permission), [
        'can_write' => true,
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Permission updated.');
    assertDatabaseHas('permissions', [
        'id' => $permission->id,
        'can_write' => true,
    ]);

    $deleteResponse = from($backUrl)->delete(route('permissions.destroy', $permission));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Permission deleted.');
    assertDatabaseMissing('permissions', ['id' => $permission->id]);
});
