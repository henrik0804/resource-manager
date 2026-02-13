<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Permission;

final class DeletePermissionAction
{
    public function handle(Permission $permission): void
    {
        $permission->delete();
    }
}
