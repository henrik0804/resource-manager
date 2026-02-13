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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TaskRequirementController
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $taskRequirements = TaskRequirement::query()
            ->with(['task', 'qualification'])
            ->when($search, fn ($query, $search) => $query
                ->whereHas('task', fn ($q) => $q->where('title', 'like', "%{$search}%"))
                ->orWhereHas('qualification', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('task-requirements/Index', [
            'taskRequirements' => $taskRequirements,
            'search' => $search,
        ]);
    }

    public function store(StoreTaskRequirementRequest $request, StoreTaskRequirementAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task requirement created.',
        ]);
    }

    public function update(UpdateTaskRequirementRequest $request, TaskRequirement $taskRequirement, UpdateTaskRequirementAction $action): RedirectResponse
    {
        $action->handle($taskRequirement, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task requirement updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, TaskRequirement $taskRequirement, DeleteTaskRequirementAction $action): RedirectResponse
    {
        $action->handle($taskRequirement);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Task requirement deleted.',
        ]);
    }
}
