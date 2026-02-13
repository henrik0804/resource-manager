<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskRequirement;

final class DeleteTaskRequirementAction
{
    public function handle(TaskRequirement $requirement): void
    {
        $requirement->delete();
    }
}
