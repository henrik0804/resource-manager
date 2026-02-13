<?php

declare(strict_types=1);

use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('resources can be managed', function (): void {
    $backUrl = '/dashboard';
    $resourceType = ResourceType::factory()->create();

    $storeResponse = from($backUrl)->post(route('resources.store'), [
        'name' => 'Room A',
        'resource_type_id' => $resourceType->id,
        'capacity_value' => 8,
        'capacity_unit' => 'seats',
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource created.');
    $resource = Resource::query()->where('name', 'Room A')->first();

    expect($resource)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('resources.update', $resource), [
        'capacity_unit' => 'workstations',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource updated.');
    assertDatabaseHas('resources', [
        'id' => $resource->id,
        'capacity_unit' => 'workstations',
    ]);

    $deleteResponse = from($backUrl)->delete(route('resources.destroy', $resource));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource deleted.');
    assertDatabaseMissing('resources', ['id' => $resource->id]);
});
