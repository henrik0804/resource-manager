<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Resource;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreResourceAction
{
    public function __construct(
        private StoreResourceTypeAction $storeResourceType,
        private StoreUserAction $storeUser,
    ) {}

    /**
     * @param array{
     *   name: string,
     *   resource_type_id?: int|null,
     *   resource_type?: array{name: string, description?: string|null}|null,
     *   capacity_value?: float|int|string|null,
     *   capacity_unit?: string|null,
     *   user_id?: int|null,
     *   user?: array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role_id?: int|null,
     *     role?: array{name: string, description?: string|null}|null
     *   }|null
     * } $data
     */
    public function handle(array $data): Resource
    {
        return DB::transaction(function () use ($data): Resource {
            if (($data['resource_type_id'] ?? null) !== null && array_key_exists('resource_type', $data)) {
                throw new InvalidArgumentException('Provide either resource_type_id or resource_type data, not both.');
            }

            if (($data['user_id'] ?? null) !== null && array_key_exists('user', $data)) {
                throw new InvalidArgumentException('Provide either user_id or user data, not both.');
            }

            $resourceTypeId = $data['resource_type_id'] ?? null;

            if ($resourceTypeId === null && isset($data['resource_type'])) {
                $resourceTypeId = $this->storeResourceType->handle($data['resource_type'])->id;
            }

            if ($resourceTypeId === null) {
                throw new InvalidArgumentException('Resource type data is required to create a resource.');
            }

            $userId = $data['user_id'] ?? null;

            if ($userId === null && isset($data['user'])) {
                $userId = $this->storeUser->handle($data['user'])->id;
            }

            return Resource::create([
                'name' => $data['name'],
                'resource_type_id' => $resourceTypeId,
                'capacity_value' => $data['capacity_value'] ?? null,
                'capacity_unit' => $data['capacity_unit'] ?? null,
                'user_id' => $userId,
            ]);
        });
    }
}
