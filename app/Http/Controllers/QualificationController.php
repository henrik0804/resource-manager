<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteQualificationAction;
use App\Actions\StoreQualificationAction;
use App\Actions\UpdateQualificationAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use Illuminate\Http\RedirectResponse;

class QualificationController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Qualifications loaded.');
    }

    public function show(Qualification $qualification): RedirectResponse
    {
        return $this->backSuccess('Qualification loaded.');
    }

    public function store(StoreQualificationRequest $request, StoreQualificationAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Qualification created.',
            'Unable to create qualification.'
        );
    }

    public function update(UpdateQualificationRequest $request, Qualification $qualification, UpdateQualificationAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($qualification, $request->validated()),
            'Qualification updated.',
            'Unable to update qualification.'
        );
    }

    public function destroy(DestroyRequest $request, Qualification $qualification, DeleteQualificationAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($qualification),
            'Qualification deleted.',
            'Unable to delete qualification.'
        );
    }
}
