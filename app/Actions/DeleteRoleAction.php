<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\HasDependentRelationshipsException;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

final class DeleteRoleAction
{
    public function handle(Role $role, bool $confirmDependencyDeletion = false): void
    {
        $dependents = $this->countDependents($role);

        if ($dependents !== [] && ! $confirmDependencyDeletion) {
            throw new HasDependentRelationshipsException($dependents);
        }

        DB::transaction(function () use ($role): void {
            foreach ($role->users as $user) {
                $resource = $user->resource;

                if ($resource) {
                    $resource->resourceQualifications()->delete();
                    $resource->taskAssignments()->delete();
                    $resource->resourceAbsences()->delete();
                    $resource->delete();
                }

                $user->delete();
            }

            $role->permissions()->delete();

            $role->delete();
        });
    }

    /**
     * @return array<string, int>
     */
    private function countDependents(Role $role): array
    {
        $dependents = [];

        $usersCount = $role->users()->count();
        if ($usersCount > 0) {
            $dependents['users'] = $usersCount;
        }

        return $dependents;
    }
}
