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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ResourceAbsenceController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', ResourceAbsence::class);

        $search = $request->string('search')->toString();

        $resourceAbsences = ResourceAbsence::query()
            ->with('resource')
            ->when($search, fn ($query, $search) => $query->whereHas('resource', fn ($q) => $q->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('resource-absences/Index', [
            'resourceAbsences' => $resourceAbsences,
            'search' => $search,
        ]);
    }

    public function store(StoreResourceAbsenceRequest $request, StoreResourceAbsenceAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource absence created.',
        ]);
    }

    public function update(UpdateResourceAbsenceRequest $request, ResourceAbsence $resourceAbsence, UpdateResourceAbsenceAction $action): RedirectResponse
    {
        $action->handle($resourceAbsence, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource absence updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, ResourceAbsence $resourceAbsence, DeleteResourceAbsenceAction $action): RedirectResponse
    {
        Gate::authorize('delete', $resourceAbsence);

        $action->handle($resourceAbsence);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Resource absence deleted.',
        ]);
    }
}
