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
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QualificationController extends Controller
{
    public function index(Request $request): Response
    {
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
