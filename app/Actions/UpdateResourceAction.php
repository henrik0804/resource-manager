<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Resource;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateResourceAction
{
    public function __construct(
        private StoreResourceTypeAction $storeResourceType,
        private StoreUserAction $storeUser,
    ) {}

    /**
     * @param array{
     *   name?: string,
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
    public function handle(Resource $resource, array $data): Resource
    {
        return DB::transaction(function () use ($resource, $data): Resource {
            if (array_key_exists('resource_type_id', $data) && array_key_exists('resource_type', $data)) {
                throw new InvalidArgumentException('Provide either resource_type_id or resource_type data, not both.');
            }

            if (array_key_exists('user_id', $data) && array_key_exists('user', $data)) {
                throw new InvalidArgumentException('Provide either user_id or user data, not both.');
            }

            $attributes = [];

            foreach (['name', 'capacity_value', 'capacity_unit'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $data[$key];
                }
            }

            if (array_key_exists('resource_type', $data)) {
                $attributes['resource_type_id'] = $this->storeResourceType->handle($data['resource_type'])->id;
            } elseif (array_key_exists('resource_type_id', $data)) {
                $attributes['resource_type_id'] = $data['resource_type_id'];
            }

            if (array_key_exists('user', $data)) {
                $attributes['user_id'] = $this->storeUser->handle($data['user'])->id;
            } elseif (array_key_exists('user_id', $data)) {
                $attributes['user_id'] = $data['user_id'];
            }

            if ($attributes !== []) {
                $resource->fill($attributes);
                $resource->save();
            }

            return $resource;
        });
    }
}
