<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final class DeleteUserAction
{
    public function handle(User $user): void
    {
        $user->delete();
    }
}
