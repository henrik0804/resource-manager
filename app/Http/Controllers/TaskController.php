<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTaskAction;
use App\Actions\StoreTaskAction;
use App\Actions\UpdateTaskAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;

class TaskController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Tasks loaded.');
    }

    public function show(Task $task): RedirectResponse
    {
        return $this->backSuccess('Task loaded.');
    }

    public function store(StoreTaskRequest $request, StoreTaskAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Task created.',
            'Unable to create task.'
        );
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($task, $request->validated()),
            'Task updated.',
            'Unable to update task.'
        );
    }

    public function destroy(DestroyRequest $request, Task $task, DeleteTaskAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($task),
            'Task deleted.',
            'Unable to delete task.'
        );
    }
}
