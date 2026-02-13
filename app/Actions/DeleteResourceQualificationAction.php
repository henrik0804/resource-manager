<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceQualification;

final class DeleteResourceQualificationAction
{
    public function handle(ResourceQualification $qualification): void
    {
        $qualification->delete();
    }
}
