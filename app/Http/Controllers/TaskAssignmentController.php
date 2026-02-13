<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTaskAssignmentAction;
use App\Actions\StoreTaskAssignmentAction;
use App\Actions\UpdateTaskAssignmentAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreTaskAssignmentRequest;
use App\Http\Requests\UpdateTaskAssignmentRequest;
use App\Models\TaskAssignment;
use Illuminate\Http\RedirectResponse;

class TaskAssignmentController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Task assignments loaded.');
    }

    public function show(TaskAssignment $taskAssignment): RedirectResponse
    {
        return $this->backSuccess('Task assignment loaded.');
    }

    public function store(StoreTaskAssignmentRequest $request, StoreTaskAssignmentAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Task assignment created.',
            'Unable to create task assignment.'
        );
    }

    public function update(UpdateTaskAssignmentRequest $request, TaskAssignment $taskAssignment, UpdateTaskAssignmentAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($taskAssignment, $request->validated()),
            'Task assignment updated.',
            'Unable to update task assignment.'
        );
    }

    public function destroy(DestroyRequest $request, TaskAssignment $taskAssignment, DeleteTaskAssignmentAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($taskAssignment),
            'Task assignment deleted.',
            'Unable to delete task assignment.'
        );
    }
}
