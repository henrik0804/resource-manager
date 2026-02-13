<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteResourceTypeAction;
use App\Actions\StoreResourceTypeAction;
use App\Actions\UpdateResourceTypeAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreResourceTypeRequest;
use App\Http\Requests\UpdateResourceTypeRequest;
use App\Models\ResourceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ResourceTypeController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ResourceType::class);

        $search = $request->string('search')->toString();

        $resourceTypes = ResourceType::query()
            ->withCount(['resources', 'qualifications'])
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('resource-types/Index', [
            'resourceTypes' => $resourceTypes,
            'search' => $search,
        ]);
    }

    public function store(StoreResourceTypeRequest $request, StoreResourceTypeAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource type created.',
        ]);
    }

    public function update(UpdateResourceTypeRequest $request, ResourceType $resourceType, UpdateResourceTypeAction $action): RedirectResponse
    {
        $action->handle($resourceType, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource type updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, ResourceType $resourceType, DeleteResourceTypeAction $action): RedirectResponse
    {
        Gate::authorize('delete', $resourceType);

        try {
            $action->handle($resourceType, $request->confirmsDependencyDeletion());
        } catch (HasDependentRelationshipsException $e) {
            return redirect()->back()->with([
                'status' => 'has_dependents',
                'dependents' => $e->dependents,
            ]);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource type deleted.',
        ]);
    }
}
