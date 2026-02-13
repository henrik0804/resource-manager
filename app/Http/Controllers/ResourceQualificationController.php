<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteResourceQualificationAction;
use App\Actions\StoreResourceQualificationAction;
use App\Actions\UpdateResourceQualificationAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreResourceQualificationRequest;
use App\Http\Requests\UpdateResourceQualificationRequest;
use App\Models\ResourceQualification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResourceQualificationController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        $resourceQualifications = ResourceQualification::query()
            ->with(['resource', 'qualification'])
            ->when($search, fn ($query, $search) => $query
                ->whereHas('resource', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orWhereHas('qualification', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('resource-qualifications/Index', [
            'resourceQualifications' => $resourceQualifications,
            'search' => $search,
        ]);
    }

    public function show(ResourceQualification $resourceQualification): RedirectResponse
    {
        return $this->backSuccess('Resource qualification loaded.');
    }

    public function store(StoreResourceQualificationRequest $request, StoreResourceQualificationAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Resource qualification created.',
            'Unable to create resource qualification.'
        );
    }

    public function update(UpdateResourceQualificationRequest $request, ResourceQualification $resourceQualification, UpdateResourceQualificationAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resourceQualification, $request->validated()),
            'Resource qualification updated.',
            'Unable to update resource qualification.'
        );
    }

    public function destroy(DestroyRequest $request, ResourceQualification $resourceQualification, DeleteResourceQualificationAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resourceQualification),
            'Resource qualification deleted.',
            'Unable to delete resource qualification.'
        );
    }
}
