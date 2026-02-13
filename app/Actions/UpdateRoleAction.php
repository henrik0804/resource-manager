<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Role;

final class UpdateRoleAction
{
    /**
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Role $role, array $data): Role
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $attributes['description'] = $data['description'];
        }

        if ($attributes !== []) {
            $role->fill($attributes);
            $role->save();
        }

        return $role;
    }
}
