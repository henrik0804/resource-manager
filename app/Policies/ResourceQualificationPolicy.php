<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccessSection;
use App\Models\ResourceQualification;
use App\Models\User;

final class ResourceQualificationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canReadSection(AccessSection::ResourceManagement);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ResourceQualification $resourceQualification): bool
    {
        return $user->canReadSection(AccessSection::ResourceManagement);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canWriteSection(AccessSection::ResourceManagement);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ResourceQualification $resourceQualification): bool
    {
        return $user->canWriteSection(AccessSection::ResourceManagement);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ResourceQualification $resourceQualification): bool
    {
        return $user->canWriteSection(AccessSection::ResourceManagement);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ResourceQualification $resourceQualification): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ResourceQualification $resourceQualification): bool
    {
        return false;
    }
}
