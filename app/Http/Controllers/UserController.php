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

class UserController extends Controller
{
    public function index(): RedirectResponse
    {
        return $this->backSuccess('Users loaded.');
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
