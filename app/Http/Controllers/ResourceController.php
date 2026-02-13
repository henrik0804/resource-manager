<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteResourceAction;
use App\Actions\StoreResourceAction;
use App\Actions\UpdateResourceAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Resource;
use Illuminate\Http\RedirectResponse;

class ResourceController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Resources loaded.');
    }

    public function show(Resource $resource): RedirectResponse
    {
        return $this->backSuccess('Resource loaded.');
    }

    public function store(StoreResourceRequest $request, StoreResourceAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Resource created.',
            'Unable to create resource.'
        );
    }

    public function update(UpdateResourceRequest $request, Resource $resource, UpdateResourceAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resource, $request->validated()),
            'Resource updated.',
            'Unable to update resource.'
        );
    }

    public function destroy(DestroyRequest $request, Resource $resource, DeleteResourceAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resource),
            'Resource deleted.',
            'Unable to delete resource.'
        );
    }
}
