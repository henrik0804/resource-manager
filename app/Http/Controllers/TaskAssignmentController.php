<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTaskAssignmentAction;
use App\Actions\StoreTaskAssignmentAction;
use App\Actions\UpdateTaskAssignmentAction;
use App\Enums\AccessSection;
use App\Enums\AssigneeStatus;
use App\Enums\AssignmentSource;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreTaskAssignmentRequest;
use App\Http\Requests\UpdateTaskAssignmentRequest;
use App\Models\Resource;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TaskAssignmentController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        $search = $request->string('search')->toString();
        $user = $request->user();

        $taskAssignmentsQuery = TaskAssignment::query()
            ->with(['task', 'resource'])
            ->when($search, fn ($query, $search) => $query
                ->whereHas('task', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->orWhereHas('resource', fn ($q) => $q->where('name', 'like', "%{$search}%")));

        if ($user && $user->canReadSection(AccessSection::EmployeeFeedback) && ! $user->canReadSection(AccessSection::ManualAssignment)) {
            $taskAssignmentsQuery->whereHas('resource', fn ($query) => $query->where('user_id', $user->id));
        }

        $taskAssignments = $taskAssignmentsQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $tasks = Task::query()->orderBy('title')->get(['id', 'title']);
        $resources = Resource::query()->orderBy('name')->get([
            'id',
            'name',
            'capacity_value',
            'capacity_unit',
        ]);

        $assignmentSources = collect(AssignmentSource::cases())
            ->map(fn (AssignmentSource $source) => [
                'value' => $source->value,
                'label' => $source->label(),
            ]);

        $assigneeStatuses = collect(AssigneeStatus::cases())
            ->map(fn (AssigneeStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]);

        return Inertia::render('task-assignments/Index', [
            'taskAssignments' => $taskAssignments,
            'tasks' => $tasks,
            'resources' => $resources,
            'assignmentSources' => $assignmentSources,
            'assigneeStatuses' => $assigneeStatuses,
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
        Gate::authorize('delete', $taskAssignment);

        $action->handle($taskAssignment);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task assignment deleted.',
        ]);
    }
}
