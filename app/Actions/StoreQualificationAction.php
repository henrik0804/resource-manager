<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Qualification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreQualificationAction
{
    public function __construct(
        private StoreResourceTypeAction $storeResourceType,
    ) {}

    /**
     * @param array{
     *   name: string,
     *   description?: string|null,
     *   resource_type_id?: int|null,
     *   resource_type?: array{name: string, description?: string|null}|null
     * } $data
     */
    public function handle(array $data): Qualification
    {
        return DB::transaction(function () use ($data): Qualification {
            if (($data['resource_type_id'] ?? null) !== null && array_key_exists('resource_type', $data)) {
                throw new InvalidArgumentException('Provide either resource_type_id or resource_type data, not both.');
            }

            $resourceTypeId = $data['resource_type_id'] ?? null;

            if ($resourceTypeId === null && isset($data['resource_type'])) {
                $resourceTypeId = $this->storeResourceType->handle($data['resource_type'])->id;
            }

            return Qualification::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'resource_type_id' => $resourceTypeId,
            ]);
        });
    }
}
