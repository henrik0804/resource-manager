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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $resources = Resource::query()
            ->with(['resourceType', 'user'])
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhereHas('resourceType', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('resources/Index', [
            'resources' => $resources,
            'search' => $search,
        ]);
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
