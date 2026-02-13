<?php

declare(strict_types=1);

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

test('resource types can be managed', function (): void {
    $backUrl = '/dashboard';

    $storeResponse = from($backUrl)->post(route('resource-types.store'), [
        'name' => 'Workspace',
        'description' => 'Shared space',
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('status', 'success');
    $resourceType = ResourceType::query()->first();

    expect($resourceType)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('resource-types.update', $resourceType), [
        'description' => 'Updated description',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource type updated.');
    assertDatabaseHas('resource_types', [
        'id' => $resourceType->id,
        'description' => 'Updated description',
    ]);

    $deleteResponse = from($backUrl)->delete(route('resource-types.destroy', $resourceType));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource type deleted.');
    assertDatabaseMissing('resource_types', ['id' => $resourceType->id]);
});
