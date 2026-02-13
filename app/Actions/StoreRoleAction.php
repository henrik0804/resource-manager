<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;

final class StoreRoleAction
{
    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function handle(array $data): Role
    {
        return Role::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
