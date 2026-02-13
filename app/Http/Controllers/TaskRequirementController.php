<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTaskRequirementAction;
use App\Actions\StoreTaskRequirementAction;
use App\Actions\UpdateTaskRequirementAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreTaskRequirementRequest;
use App\Http\Requests\UpdateTaskRequirementRequest;
use App\Models\TaskRequirement;
use Illuminate\Http\RedirectResponse;

class TaskRequirementController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Task requirements loaded.');
    }

    public function show(TaskRequirement $taskRequirement): RedirectResponse
    {
        return $this->backSuccess('Task requirement loaded.');
    }

    public function store(StoreTaskRequirementRequest $request, StoreTaskRequirementAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Task requirement created.',
            'Unable to create task requirement.'
        );
    }

    public function update(UpdateTaskRequirementRequest $request, TaskRequirement $taskRequirement, UpdateTaskRequirementAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($taskRequirement, $request->validated()),
            'Task requirement updated.',
            'Unable to update task requirement.'
        );
    }

    public function destroy(DestroyRequest $request, TaskRequirement $taskRequirement, DeleteTaskRequirementAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($taskRequirement),
            'Task requirement deleted.',
            'Unable to delete task requirement.'
        );
    }
}
