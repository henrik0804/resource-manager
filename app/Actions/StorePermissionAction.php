<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessSection;
use App\Models\Permission;

final class StorePermissionAction
{
    /**
     * @param  array{role_id: int, section: AccessSection|string, can_read: bool, can_write: bool, can_write_owned: bool}  $data
     */
    public function handle(array $data): Permission
    {
        return Permission::create([
            'role_id' => $data['role_id'],
            'section' => $data['section'],
            'can_read' => $data['can_read'],
            'can_write' => $data['can_write'],
            'can_write_owned' => $data['can_write_owned'],
        ]);
    }
}
