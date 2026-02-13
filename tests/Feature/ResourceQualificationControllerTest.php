<?php

declare(strict_types=1);

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceQualification;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    actingAsUserWithPermissions();
});

test('resource qualifications can be managed', function (): void {
    $backUrl = '/dashboard';
    $resource = Resource::factory()->create();
    $qualification = Qualification::factory()->create();

    $storeResponse = from($backUrl)->post(route('resource-qualifications.store'), [
        'resource_id' => $resource->id,
        'qualification_id' => $qualification->id,
        'level' => QualificationLevel::Advanced->value,
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource qualification created.');
    $resourceQualification = ResourceQualification::query()->where('resource_id', $resource->id)->first();

    expect($resourceQualification)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('resource-qualifications.update', $resourceQualification), [
        'level' => QualificationLevel::Expert->value,
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource qualification updated.');
    assertDatabaseHas('resource_qualifications', [
        'id' => $resourceQualification->id,
        'level' => QualificationLevel::Expert->value,
    ]);

    $deleteResponse = from($backUrl)->delete(route('resource-qualifications.destroy', $resourceQualification));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource qualification deleted.');
    assertDatabaseMissing('resource_qualifications', ['id' => $resourceQualification->id]);
});
