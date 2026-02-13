<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Qualification;

final class DeleteQualificationAction
{
    public function handle(Qualification $qualification): void
    {
        $qualification->delete();
    }
}
