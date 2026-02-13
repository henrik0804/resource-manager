<?php

declare(strict_types=1);

use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('resource absences can be managed', function (): void {
    $backUrl = '/dashboard';
    $resource = Resource::factory()->create();
    $startsAt = now()->addDay();
    $endsAt = now()->addDays(2);

    $storeResponse = from($backUrl)->post(route('resource-absences.store'), [
        'resource_id' => $resource->id,
        'starts_at' => $startsAt->toDateTimeString(),
        'ends_at' => $endsAt->toDateTimeString(),
        'recurrence_rule' => 'FREQ=WEEKLY;COUNT=2',
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource absence created.');
    $absence = ResourceAbsence::query()->where('resource_id', $resource->id)->first();

    expect($absence)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('resource-absences.update', $absence), [
        'ends_at' => $endsAt->addDay()->toDateTimeString(),
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource absence updated.');
    assertDatabaseHas('resource_absences', [
        'id' => $absence->id,
    ]);

    $deleteResponse = from($backUrl)->delete(route('resource-absences.destroy', $absence));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Resource absence deleted.');
    assertDatabaseMissing('resource_absences', ['id' => $absence->id]);
});
