<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeletePermissionAction;
use App\Actions\StorePermissionAction;
use App\Actions\UpdatePermissionAction;
use App\Enums\AccessSection;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class PermissionController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Permission::class);

        $search = $request->string('search')->toString();

        $permissions = Permission::query()
            ->with('role')
            ->when($search, fn ($query, $search) => $query
                ->where('section', 'like', "%{$search}%")
                ->orWhereHas('role', fn ($roleQuery) => $roleQuery->where('name', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $sections = collect(AccessSection::cases())
            ->map(fn (AccessSection $section) => [
                'value' => $section->value,
                'label' => $section->label(),
            ]);

        $roles = Role::query()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('permissions/Index', [
            'permissions' => $permissions,
            'roles' => $roles,
            'sections' => $sections,
            'search' => $search,
        ]);
    }

    public function store(StorePermissionRequest $request, StorePermissionAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Permission created.',
        ]);
    }

    public function update(UpdatePermissionRequest $request, Permission $permission, UpdatePermissionAction $action): RedirectResponse
    {
        $action->handle($permission, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Permission updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, Permission $permission, DeletePermissionAction $action): RedirectResponse
    {
        Gate::authorize('delete', $permission);

        $action->handle($permission);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Permission deleted.',
        ]);
    }
}
