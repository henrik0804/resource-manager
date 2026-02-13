<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceType;

final class UpdateResourceTypeAction
{
    /**
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(ResourceType $resourceType, array $data): ResourceType
    {
        $attributes = [];

        if (array_key_exists('name', $data)) {
            $attributes['name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $attributes['description'] = $data['description'];
        }

        if ($attributes !== []) {
            $resourceType->fill($attributes);
            $resourceType->save();
        }

        return $resourceType;
    }
}
