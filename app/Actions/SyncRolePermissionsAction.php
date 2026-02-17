<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AccessSection;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

final class SyncRolePermissionsAction
{
    /**
     * Sync all permissions for a role in a single operation.
     *
     * Sections not present in $permissions will have their permission record deleted.
     * Sections present will be created or updated accordingly.
     *
     * @param  array<string, array{can_read: bool, can_write: bool, can_write_owned: bool}>  $permissions
     */
    public function handle(Role $role, array $permissions): void
    {
        DB::transaction(function () use ($role, $permissions): void {
            $existingPermissions = $role->permissions()->get()->keyBy(
                fn (Permission $permission) => $permission->section->value,
            );

            $incomingSections = [];

            foreach ($permissions as $sectionValue => $flags) {
                $section = AccessSection::from($sectionValue);
                $incomingSections[] = $sectionValue;

                $hasAnyFlag = $flags['can_read'] || $flags['can_write'] || $flags['can_write_owned'];

                if (! $hasAnyFlag) {
                    // Remove permission if all flags are off
                    if ($existingPermissions->has($sectionValue)) {
                        $existingPermissions->get($sectionValue)->delete();
                    }

                    continue;
                }

                if ($existingPermissions->has($sectionValue)) {
                    $existingPermissions->get($sectionValue)->update([
                        'can_read' => $flags['can_read'],
                        'can_write' => $flags['can_write'],
                        'can_write_owned' => $flags['can_write_owned'],
                    ]);
                } else {
                    Permission::create([
                        'role_id' => $role->id,
                        'section' => $section,
                        'can_read' => $flags['can_read'],
                        'can_write' => $flags['can_write'],
                        'can_write_owned' => $flags['can_write_owned'],
                    ]);
                }
            }

            // Delete permissions for sections not included in the request
            $role->permissions()
                ->whereNotIn('section', $incomingSections)
                ->delete();
        });
    }
}
