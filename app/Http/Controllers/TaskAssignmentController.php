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

final class TaskAssignmentController
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

    public function store(StoreTaskAssignmentRequest $request, StoreTaskAssignmentAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task assignment created.',
        ]);
    }

    public function update(UpdateTaskAssignmentRequest $request, TaskAssignment $taskAssignment, UpdateTaskAssignmentAction $action): RedirectResponse
    {
        $action->handle($taskAssignment, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task assignment updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, TaskAssignment $taskAssignment, DeleteTaskAssignmentAction $action): RedirectResponse
    {
        $action->handle($taskAssignment);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task assignment deleted.',
        ]);
    }
}
