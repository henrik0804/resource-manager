<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteUserAction;
use App\Actions\StoreUserAction;
use App\Actions\UpdateUserAction;
use App\Exceptions\HasDependentRelationshipsException;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class UserController
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->toString();

        $users = User::query()
            ->with('role')
            ->when($search, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function store(StoreUserRequest $request, StoreUserAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'User created.',
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        $action->handle($user, $request->validated());

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'User updated.',
        ]);
    }

    public function destroy(DestroyRequest $request, User $user, DeleteUserAction $action): RedirectResponse
    {
        Gate::authorize('delete', $user);

        try {
            $action->handle($user, $request->confirmsDependencyDeletion());
        } catch (HasDependentRelationshipsException $e) {
            return redirect()->back()->with([
                'status' => 'has_dependents',
                'dependents' => $e->dependents,
            ]);
        }

        return redirect()->back()->with([
            'status' => 'success',
            'message' => 'User deleted.',
        ]);
    }
}
