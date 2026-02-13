<?php

declare(strict_types=1);

use App\Models\Qualification;
use App\Models\ResourceQualification;
use App\Models\ResourceType;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('qualifications can be managed', function (): void {
    $backUrl = '/dashboard';
    $resourceType = ResourceType::factory()->create();

    $storeResponse = from($backUrl)->post(route('qualifications.store'), [
        'name' => 'Project Planning',
        'description' => 'Planning skills',
        'resource_type_id' => $resourceType->id,
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Qualification created.');
    $qualification = Qualification::query()->where('name', 'Project Planning')->first();

    expect($qualification)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('qualifications.update', $qualification), [
        'description' => 'Updated skills',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Qualification updated.');
    assertDatabaseHas('qualifications', [
        'id' => $qualification->id,
        'description' => 'Updated skills',
    ]);

    $deleteResponse = from($backUrl)->delete(route('qualifications.destroy', $qualification));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Qualification deleted.');
    assertDatabaseMissing('qualifications', ['id' => $qualification->id]);
});

test('deleting qualification with dependents returns has_dependents status', function (): void {
    $backUrl = '/dashboard';
    $qualification = Qualification::factory()->create();
    ResourceQualification::factory()->create(['qualification_id' => $qualification->id]);

    $deleteResponse = from($backUrl)->delete(route('qualifications.destroy', $qualification));

    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('status', 'has_dependents');
    assertDatabaseHas('qualifications', ['id' => $qualification->id]);
});

test('deleting qualification with dependents and confirmation cascades', function (): void {
    $backUrl = '/dashboard';
    $qualification = Qualification::factory()->create();
    $resourceQualification = ResourceQualification::factory()->create(['qualification_id' => $qualification->id]);

    $deleteResponse = from($backUrl)->delete(route('qualifications.destroy', $qualification), [
        'confirm_dependency_deletion' => true,
    ]);

    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Qualification deleted.');
    assertDatabaseMissing('qualifications', ['id' => $qualification->id]);
    assertDatabaseMissing('resource_qualifications', ['id' => $resourceQualification->id]);
});
