<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteResourceTypeAction;
use App\Actions\StoreResourceTypeAction;
use App\Actions\UpdateResourceTypeAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreResourceTypeRequest;
use App\Http\Requests\UpdateResourceTypeRequest;
use App\Models\ResourceType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceTypeController extends Controller
{
    public function index(Request $request): Response
    {
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

    public function show(ResourceType $resourceType): RedirectResponse
    {
        return $this->backSuccess('Resource type loaded.');
    }

    public function store(StoreResourceTypeRequest $request, StoreResourceTypeAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Resource type created.',
            'Unable to create resource type.'
        );
    }

    public function update(UpdateResourceTypeRequest $request, ResourceType $resourceType, UpdateResourceTypeAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resourceType, $request->validated()),
            'Resource type updated.',
            'Unable to update resource type.'
        );
    }

    public function destroy(DestroyRequest $request, ResourceType $resourceType, DeleteResourceTypeAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resourceType),
            'Resource type deleted.',
            'Unable to delete resource type.'
        );
    }
}
