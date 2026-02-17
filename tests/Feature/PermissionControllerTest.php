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

test('permissions can be synced for a role', function (): void {
    $backUrl = '/permissions';
    $role = Role::factory()->create();

    // Create an existing permission that should be updated
    Permission::factory()->create([
        'role_id' => $role->id,
        'section' => AccessSection::ResourceManagement,
        'can_read' => true,
        'can_write' => false,
        'can_write_owned' => false,
    ]);

    $response = from($backUrl)->put(route('permissions.sync', $role), [
        'permissions' => [
            AccessSection::ResourceManagement->value => [
                'can_read' => true,
                'can_write' => true,
                'can_write_owned' => false,
            ],
            AccessSection::TaskCreation->value => [
                'can_read' => true,
                'can_write' => false,
                'can_write_owned' => true,
            ],
            // Section with all flags off should not create a record
            AccessSection::VisualOverview->value => [
                'can_read' => false,
                'can_write' => false,
                'can_write_owned' => false,
            ],
        ],
    ]);

    $response->assertRedirect($backUrl)->assertSessionHas('message', 'Permissions synced.');

    // Existing permission was updated
    assertDatabaseHas('permissions', [
        'role_id' => $role->id,
        'section' => AccessSection::ResourceManagement->value,
        'can_read' => true,
        'can_write' => true,
        'can_write_owned' => false,
    ]);

    // New permission was created
    assertDatabaseHas('permissions', [
        'role_id' => $role->id,
        'section' => AccessSection::TaskCreation->value,
        'can_read' => true,
        'can_write' => false,
        'can_write_owned' => true,
    ]);

    // All-false section was not created
    assertDatabaseMissing('permissions', [
        'role_id' => $role->id,
        'section' => AccessSection::VisualOverview->value,
    ]);
});

test('sync removes permissions for sections not in the request', function (): void {
    $backUrl = '/permissions';
    $role = Role::factory()->create();

    $orphanPermission = Permission::factory()->create([
        'role_id' => $role->id,
        'section' => AccessSection::EmployeeFeedback,
        'can_read' => true,
        'can_write' => true,
        'can_write_owned' => false,
    ]);

    $response = from($backUrl)->put(route('permissions.sync', $role), [
        'permissions' => [
            AccessSection::ResourceManagement->value => [
                'can_read' => true,
                'can_write' => false,
                'can_write_owned' => false,
            ],
        ],
    ]);

    $response->assertRedirect($backUrl);

    // The section that was not included in the sync should be deleted
    assertDatabaseMissing('permissions', ['id' => $orphanPermission->id]);
});
