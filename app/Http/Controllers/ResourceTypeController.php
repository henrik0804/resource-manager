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

class ResourceTypeController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Resource types loaded.');
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
