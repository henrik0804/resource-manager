<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeletePermissionAction;
use App\Actions\StorePermissionAction;
use App\Actions\SyncRolePermissionsAction;
use App\Actions\UpdatePermissionAction;
use App\Enums\AccessSection;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\SyncRolePermissionsRequest;
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

        $sections = collect(AccessSection::cases())
            ->map(fn (AccessSection $section) => [
                'value' => $section->value,
                'label' => $section->label(),
            ]);

        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions->keyBy(
                    fn (Permission $p) => $p->section->value,
                )->map(fn (Permission $p) => [
                    'can_read' => $p->can_read,
                    'can_write' => $p->can_write,
                    'can_write_owned' => $p->can_write_owned,
                ]),
            ]);

        $selectedRoleId = $request->integer('role');

        return Inertia::render('permissions/Index', [
            'roles' => $roles,
            'sections' => $sections,
            'selectedRoleId' => $selectedRoleId ?: null,
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

    public function sync(SyncRolePermissionsRequest $request, Role $role, SyncRolePermissionsAction $action): RedirectResponse
    {
        $action->handle($role, $request->validated()['permissions']);

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'Permissions synced.',
        ]);
    }
}
