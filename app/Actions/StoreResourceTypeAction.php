<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceType;

final class StoreResourceTypeAction
{
    /**
     * @param  array{name: string, description?: string|null}  $data
     */
    public function handle(array $data): ResourceType
    {
        return ResourceType::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }
}
