<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    actingAsUserWithPermissions();
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
        'assignee_status' => 'accepted',
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task assignment updated.');
    assertDatabaseHas('task_assignments', [
        'id' => $assignment->id,
        'assignee_status' => 'accepted',
    ]);

    $deleteResponse = from($backUrl)->delete(route('task-assignments.destroy', $assignment));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task assignment deleted.');
    assertDatabaseMissing('task_assignments', ['id' => $assignment->id]);
});

test('employees can update only their assignment status', function (): void {
    $backUrl = '/dashboard';
    $employee = actingAsUserWithPermissions([
        'read' => [AccessSection::EmployeeFeedback],
        'write' => [],
        'write_owned' => [AccessSection::EmployeeFeedback],
    ]);

    $task = Task::factory()->create();
    $employeeResource = Resource::factory()->create(['user_id' => $employee->id]);
    $otherResource = Resource::factory()->create();

    $employeeAssignment = TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'resource_id' => $employeeResource->id,
    ]);

    $otherAssignment = TaskAssignment::factory()->create([
        'task_id' => $task->id,
        'resource_id' => $otherResource->id,
    ]);

    from($backUrl)->put(route('task-assignments.update', $employeeAssignment), [
        'assignee_status' => 'in_progress',
    ])->assertRedirect($backUrl)->assertSessionHas('message', 'Task assignment updated.');

    assertDatabaseHas('task_assignments', [
        'id' => $employeeAssignment->id,
        'assignee_status' => 'in_progress',
    ]);

    from($backUrl)->put(route('task-assignments.update', $otherAssignment), [
        'assignee_status' => 'in_progress',
    ])->assertForbidden();
});
