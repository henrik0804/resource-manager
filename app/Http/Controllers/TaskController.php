<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteTaskAction;
use App\Actions\StoreTaskAction;
use App\Actions\UpdateTaskAction;
use App\Enums\EffortUnit;
use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Exceptions\HasDependentRelationshipsException;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TaskController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Task::class);

        $search = $request->string('search')->toString();

        $tasks = Task::query()
            ->when($search, fn ($query, $search) => $query->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $priorities = collect(TaskPriority::cases())
            ->map(fn (TaskPriority $priority) => [
                'value' => $priority->value,
                'label' => $priority->label(),
            ]);

        $statuses = collect(TaskStatus::cases())
            ->map(fn (TaskStatus $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ]);

        $effortUnits = collect(EffortUnit::cases())
            ->map(fn (EffortUnit $unit) => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ]);

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'priorities' => $priorities,
            'statuses' => $statuses,
            'effortUnits' => $effortUnits,
            'search' => $search,
        ]);
    }

    public function store(StoreTaskRequest $request, StoreTaskAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task created.',
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task, UpdateTaskAction $action): RedirectResponse
    {
        $action->handle($task, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, Task $task, DeleteTaskAction $action): RedirectResponse
    {
        Gate::authorize('delete', $task);

        try {
            $action->handle($task, $request->confirmsDependencyDeletion());
        } catch (HasDependentRelationshipsException $e) {
            return redirect()->back()->with([
                'status' => 'has_dependents',
                'dependents' => $e->dependents,
            ]);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task deleted.',
        ]);
    }
}
