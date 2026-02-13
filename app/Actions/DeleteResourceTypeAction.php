<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceType;

final class DeleteResourceTypeAction
{
    public function handle(ResourceType $resourceType): void
    {
        $resourceType->delete();
    }
}
