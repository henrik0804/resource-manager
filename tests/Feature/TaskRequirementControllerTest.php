<?php

declare(strict_types=1);

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Task;
use App\Models\TaskRequirement;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\from;

beforeEach(function (): void {
    $user = User::factory()->create();
    actingAs($user);
});

test('task requirements can be managed', function (): void {
    $backUrl = '/dashboard';
    $task = Task::factory()->create();
    $qualification = Qualification::factory()->create();

    $storeResponse = from($backUrl)->post(route('task-requirements.store'), [
        'task_id' => $task->id,
        'qualification_id' => $qualification->id,
        'required_level' => QualificationLevel::Intermediate->value,
    ]);

    $storeResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task requirement created.');
    $requirement = TaskRequirement::query()->where('task_id', $task->id)->first();

    expect($requirement)->not()->toBeNull();

    $updateResponse = from($backUrl)->put(route('task-requirements.update', $requirement), [
        'required_level' => QualificationLevel::Advanced->value,
    ]);

    $updateResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task requirement updated.');
    assertDatabaseHas('task_requirements', [
        'id' => $requirement->id,
        'required_level' => QualificationLevel::Advanced->value,
    ]);

    $deleteResponse = from($backUrl)->delete(route('task-requirements.destroy', $requirement));
    $deleteResponse->assertRedirect($backUrl)->assertSessionHas('message', 'Task requirement deleted.');
    assertDatabaseMissing('task_requirements', ['id' => $requirement->id]);
});
