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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $taskAssignments = TaskAssignment::query()
            ->with(['task', 'resource'])
            ->when($search, fn ($query, $search) => $query
                ->whereHas('task', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->orWhereHas('resource', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('task-assignments/Index', [
            'taskAssignments' => $taskAssignments,
            'search' => $search,
        ]);
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
