<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\DeleteUserAction;
use App\Actions\StoreUserAction;
use App\Actions\UpdateUserAction;
use App\Http\Requests\DestroyRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
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

    public function show(User $user): RedirectResponse
    {
        return $this->backSuccess('User loaded.');
    }

    public function store(StoreUserRequest $request, StoreUserAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($request->validated()),
            'User created.',
            'Unable to create user.'
        );
    }

    public function update(UpdateUserRequest $request, User $user, UpdateUserAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($user, $request->validated()),
            'User updated.',
            'Unable to update user.'
        );
    }

    public function destroy(DestroyRequest $request, User $user, DeleteUserAction $action): RedirectResponse
    {
        return $this->handleAction(
            fn () => $action->handle($user),
            'User deleted.',
            'Unable to delete user.'
        );
    }
}
