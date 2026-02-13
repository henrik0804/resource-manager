<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessSection;
use App\Models\Permission;

final class UpdatePermissionAction
{
    /**
     * @param  array{role_id?: int, section?: AccessSection|string, can_read?: bool, can_write?: bool, can_write_owned?: bool}  $data
     */
    public function handle(Permission $permission, array $data): Permission
    {
        $permission->fill($data);
        $permission->save();

        return $permission;
    }
}
