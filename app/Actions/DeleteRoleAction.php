<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;

final class DeleteRoleAction
{
    public function handle(Role $role): void
    {
        $role->delete();
    }
}
