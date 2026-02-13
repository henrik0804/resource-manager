<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Qualification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateQualificationAction
{
    public function __construct(
        private StoreResourceTypeAction $storeResourceType,
    ) {}

    /**
     * @param array{
     *   name?: string,
     *   description?: string|null,
     *   resource_type_id?: int|null,
     *   resource_type?: array{name: string, description?: string|null}|null
     * } $data
     */
    public function handle(Qualification $qualification, array $data): Qualification
    {
        return DB::transaction(function () use ($qualification, $data): Qualification {
            if (array_key_exists('resource_type_id', $data) && array_key_exists('resource_type', $data)) {
                throw new InvalidArgumentException('Provide either resource_type_id or resource_type data, not both.');
            }

            $attributes = [];

            if (array_key_exists('name', $data)) {
                $attributes['name'] = $data['name'];
            }

            if (array_key_exists('description', $data)) {
                $attributes['description'] = $data['description'];
            }

            if (array_key_exists('resource_type', $data)) {
                $attributes['resource_type_id'] = $this->storeResourceType->handle($data['resource_type'])->id;
            } elseif (array_key_exists('resource_type_id', $data)) {
                $attributes['resource_type_id'] = $data['resource_type_id'];
            }

            if ($attributes !== []) {
                $qualification->fill($attributes);
                $qualification->save();
            }

            return $qualification;
        });
    }
}
