<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Resource;

final class DeleteResourceAction
{
    public function handle(Resource $resource): void
    {
        $resource->delete();
    }
}
