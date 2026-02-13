<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteRoleAction;
use App\Actions\StoreRoleAction;
use App\Actions\UpdateRoleAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
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

    public function show(Role $role): RedirectResponse
    {
        return $this->backSuccess('Role loaded.');
    }

    public function store(StoreRoleRequest $request, StoreRoleAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'Role created.',
            'Unable to create role.'
        );
    }

    public function update(UpdateRoleRequest $request, Role $role, UpdateRoleAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($role, $request->validated()),
            'Role updated.',
            'Unable to update role.'
        );
    }

    public function destroy(DestroyRequest $request, Role $role, DeleteRoleAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($role),
            'Role deleted.',
            'Unable to delete role.'
        );
    }
}
