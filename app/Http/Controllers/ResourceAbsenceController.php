<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteResourceAbsenceAction;
use App\Actions\StoreResourceAbsenceAction;
use App\Actions\UpdateResourceAbsenceAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreResourceAbsenceRequest;
use App\Http\Requests\UpdateResourceAbsenceRequest;
use App\Models\ResourceAbsence;
use Illuminate\Http\RedirectResponse;

class ResourceAbsenceController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Resource absences loaded.');
    }

    public function show(ResourceAbsence $resourceAbsence): RedirectResponse
    {
        return $this->backSuccess('Resource absence loaded.');
    }

    public function store(StoreResourceAbsenceRequest $request, StoreResourceAbsenceAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Resource absence created.',
            'Unable to create resource absence.'
        );
    }

    public function update(UpdateResourceAbsenceRequest $request, ResourceAbsence $resourceAbsence, UpdateResourceAbsenceAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resourceAbsence, $request->validated()),
            'Resource absence updated.',
            'Unable to update resource absence.'
        );
    }

    public function destroy(DestroyRequest $request, ResourceAbsence $resourceAbsence, DeleteResourceAbsenceAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($resourceAbsence),
            'Resource absence deleted.',
            'Unable to delete resource absence.'
        );
    }
}
