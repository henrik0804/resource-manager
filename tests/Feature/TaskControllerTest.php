<?php

declare(strict_types=1);

use App\Models\Task;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('tasks can be managed', function (): void {
    $backUrl = '/dashboard';
    $startsAt = now()->addDays(3);
    $endsAt = now()->addDays(5);

    $storeResponse = from($backUrl)->post(route('tasks.store'), [
        'title' => 'Release planning',
        'description' => 'Plan the next release',
        'starts_at' => $startsAt->toDateTimeString(),
        'ends_at' => $endsAt->toDateTimeString(),
        'effort_value' => 12,
        'effort_unit' => 'hours',
        'priority' => 'high',
        'status' => 'planned',
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task created.');
    $task = Task::query()->where('title', 'Release planning')->first();

    expect($task)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('tasks.update', $task), [
        'status' => 'in_progress',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task updated.');
    assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => 'in_progress',
    ]);

    $deleteResponse = from($backUrl)->delete(route('tasks.destroy', $task));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task deleted.');
    assertDatabaseMissing('tasks', ['id' => $task->id]);
});
