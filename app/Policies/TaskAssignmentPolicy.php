<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\AccessSection;
use App\Models\TaskAssignment;
use App\Models\User;

final class TaskAssignmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canReadSection(AccessSection::ManualAssignment)
            || $user->canReadSection(AccessSection::EmployeeFeedback);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TaskAssignment $taskAssignment): bool
    {
        if ($user->canReadSection(AccessSection::ManualAssignment)) {
            return true;
        }

        return $user->canReadSection(AccessSection::EmployeeFeedback)
            && $taskAssignment->isOwnedBy($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canWriteSection(AccessSection::ManualAssignment);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TaskAssignment $taskAssignment): bool
    {
        if ($user->canWriteSection(AccessSection::ManualAssignment)) {
            return true;
        }

        return $user->canWriteOwnedSection(AccessSection::EmployeeFeedback)
            && $taskAssignment->isOwnedBy($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TaskAssignment $taskAssignment): bool
    {
        return $user->canWriteSection(AccessSection::ManualAssignment);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TaskAssignment $taskAssignment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TaskAssignment $taskAssignment): bool
    {
        return false;
    }
}
