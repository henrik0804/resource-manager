<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceAbsence;

final class DeleteResourceAbsenceAction
{
    public function handle(ResourceAbsence $absence): void
    {
        $absence->delete();
    }
}
