<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteResourceAction;
use App\Actions\StoreResourceAction;
use App\Actions\UpdateResourceAction;
use App\Enums\CapacityUnit;
use App\Exceptions\HasDependentRelationshipsException;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ResourceController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Resource::class);

        $search = $request->string('search')->toString();

        $resources = Resource::query()
            ->with(['resourceType', 'user'])
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('resourceType', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $resourceTypes = ResourceType::query()->orderBy('name')->get(['id', 'name']);
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        $capacityUnits = collect(CapacityUnit::cases())
            ->map(fn (CapacityUnit $unit) => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ]);

        return Inertia::render('resources/Index', [
            'resources' => $resources,
            'resourceTypes' => $resourceTypes,
            'users' => $users,
            'capacityUnits' => $capacityUnits,
            'search' => $search,
        ]);
    }

    public function store(StoreResourceRequest $request, StoreResourceAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource created.',
        ]);
    }

    public function update(UpdateResourceRequest $request, Resource $resource, UpdateResourceAction $action): RedirectResponse
    {
        $action->handle($resource, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, Resource $resource, DeleteResourceAction $action): RedirectResponse
    {
        Gate::authorize('delete', $resource);

        try {
            $action->handle($resource, $request->confirmsDependencyDeletion());
        } catch (HasDependentRelationshipsException $e) {
            return redirect()->back()->with([
                'status' => 'has_dependents',
                'dependents' => $e->dependents,
            ]);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource deleted.',
        ]);
    }
}
