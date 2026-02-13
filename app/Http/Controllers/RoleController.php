<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteRoleAction;
use App\Actions\StoreRoleAction;
use App\Actions\UpdateRoleAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class RoleController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Role::class);

        $search = $request->string('search')->toString();

        $roles = Role::query()
            ->withCount('users')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('roles/Index', [
            'roles' => $roles,
            'search' => $search,
        ]);
    }

    public function store(StoreRoleRequest $request, StoreRoleAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Role created.',
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        $action->handle($role, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Role updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, Role $role, DeleteRoleAction $action): RedirectResponse
    {
        Gate::authorize('delete', $role);

        try {
            $action->handle($role, $request->confirmsDependencyDeletion());
        } catch (HasDependentRelationshipsException $e) {
            return redirect()->back()->with([
                'status' => 'has_dependents',
                'dependents' => $e->dependents,
            ]);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Role deleted.',
        ]);
    }
}
