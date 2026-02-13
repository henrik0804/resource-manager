<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaskAssignment;

final class DeleteTaskAssignmentAction
{
    public function handle(TaskAssignment $assignment): void
    {
        $assignment->delete();
    }
}
