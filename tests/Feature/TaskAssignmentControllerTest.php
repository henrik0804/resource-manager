<?php

declare(strict_types=1);

use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('task assignments can be managed', function (): void {
    $backUrl = '/dashboard';
    $task = Task::factory()->create();
    $resource = Resource::factory()->create();

    $storeResponse = from($backUrl)->post(route('task-assignments.store'), [
        'task_id' => $task->id,
        'resource_id' => $resource->id,
        'assignment_source' => 'manual',
        'allocation_ratio' => 0.5,
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task assignment created.');
    $assignment = TaskAssignment::query()->where('task_id', $task->id)->first();

    expect($assignment)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('task-assignments.update', $assignment), [
        'assignee_status' => 'confirmed',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task assignment updated.');
    assertDatabaseHas('task_assignments', [
        'id' => $assignment->id,
        'assignee_status' => 'confirmed',
    ]);

    $deleteResponse = from($backUrl)->delete(route('task-assignments.destroy', $assignment));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task assignment deleted.');
    assertDatabaseMissing('task_assignments', ['id' => $assignment->id]);
});
