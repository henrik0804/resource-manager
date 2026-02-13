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

class ResourceQualificationController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Resource qualifications loaded.');
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
