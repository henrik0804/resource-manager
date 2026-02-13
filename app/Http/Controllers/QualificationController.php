<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteQualificationAction;
use App\Actions\StoreQualificationAction;
use App\Actions\UpdateQualificationAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreQualificationRequest;
use App\Http\Requests\UpdateQualificationRequest;
use App\Models\Qualification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class QualificationController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Qualification::class);

        $search = $request->string('search')->toString();

        $qualifications = Qualification::query()
            ->with('resourceType')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('qualifications/Index', [
            'qualifications' => $qualifications,
            'search' => $search,
        ]);
    }

    public function store(StoreQualificationRequest $request, StoreQualificationAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Qualification created.',
        ]);
    }

    public function update(UpdateQualificationRequest $request, Qualification $qualification, UpdateQualificationAction $action): RedirectResponse
    {
        $action->handle($qualification, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Qualification updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, Qualification $qualification, DeleteQualificationAction $action): RedirectResponse
    {
        Gate::authorize('delete', $qualification);

        try {
            $action->handle($qualification, $request->confirmsDependencyDeletion());
        } catch (HasDependentRelationshipsException $e) {
            return redirect()->back()->with([
                'status' => 'has_dependents',
                'dependents' => $e->dependents,
            ]);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Qualification deleted.',
        ]);
    }
}
