<?php

declare(strict_types=1);

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\TaskAssignment;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('resources can be managed', function (): void {
    $backUrl = '/dashboard';
    $resourceType = ResourceType::factory()->create();

    $storeResponse = from($backUrl)->post(route('resources.store'), [
        'name' => 'Room A',
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 8,
        'capacity_unit' => 'slots',
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource created.');
    $resource = Resource::query()->where('name', 'Room A')->first();

    expect($resource)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('resources.update', $resource), [
        'capacity_unit' => 'slots',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource updated.');
    assertDatabaseHas('resources', [
        'id' => $resource->id,
        'capacity_unit' => 'slots',
    ]);

    $deleteResponse = from($backUrl)->delete(route('resources.destroy', $resource));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource deleted.');
    assertDatabaseMissing('resources', ['id' => $resource->id]);
});

test('deleting resource with dependents returns has_dependents status', function (): void {
    $backUrl = '/dashboard';
    $resource = Resource::factory()->create();
    TaskAssignment::factory()->create(['resource_id' => $resource->id]);

    $deleteResponse = from($backUrl)->delete(route('resources.destroy', $resource));

    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('status', 'has_dependents');
    assertDatabaseHas('resources', ['id' => $resource->id]);
});

test('deleting resource with dependents and confirmation cascades', function (): void {
    $backUrl = '/dashboard';
    $resource = Resource::factory()->create();
    $assignment = TaskAssignment::factory()->create(['resource_id' => $resource->id]);

    $deleteResponse = from($backUrl)->delete(route('resources.destroy', $resource), [
        'confirm_dependency_deletion' => true,
    ]);

    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource deleted.');
    assertDatabaseMissing('resources', ['id' => $resource->id]);
    assertDatabaseMissing('task_assignments', ['id' => $assignment->id]);
});
