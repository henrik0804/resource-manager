<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QualificationLevel;
use App\Models\ResourceQualification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateResourceQualificationAction
{
    public function __construct(
        private StoreResourceAction $storeResource,
        private StoreQualificationAction $storeQualification,
    ) {}

    /**
     * @param array{
     *   resource_id?: int|null,
     *   resource?: array{
     *     name: string,
     *     resource_type_id?: int|null,
     *     resource_type?: array{name: string, description?: string|null}|null,
     *     capacity_value?: float|int|string|null,
     *     capacity_unit?: string|null,
     *     user_id?: int|null,
     *     user?: array{
     *       name: string,
     *       email: string,
     *       password: string,
     *       role_id?: int|null,
     *       role?: array{name: string, description?: string|null}|null
     *     }|null
     *   }|null,
     *   qualification_id?: int|null,
     *   qualification?: array{
     *     name: string,
     *     description?: string|null,
     *     resource_type_id?: int|null,
     *     resource_type?: array{name: string, description?: string|null}|null
     *   }|null,
     *   level?: QualificationLevel|string|null
     * } $data
     */
    public function handle(ResourceQualification $qualification, array $data): ResourceQualification
    {
        return DB::transaction(function () use ($qualification, $data): ResourceQualification {
            if (array_key_exists('resource_id', $data) && array_key_exists('resource', $data)) {
                throw new InvalidArgumentException('Provide either resource_id or resource data, not both.');
            }

            if (array_key_exists('qualification_id', $data) && array_key_exists('qualification', $data)) {
                throw new InvalidArgumentException('Provide either qualification_id or qualification data, not both.');
            }

            $attributes = [];

            if (array_key_exists('resource', $data)) {
                $attributes['resource_id'] = $this->storeResource->handle($data['resource'])->id;
            } elseif (array_key_exists('resource_id', $data)) {
                $attributes['resource_id'] = $data['resource_id'];
            }

            if (array_key_exists('qualification', $data)) {
                $attributes['qualification_id'] = $this->storeQualification->handle($data['qualification'])->id;
            } elseif (array_key_exists('qualification_id', $data)) {
                $attributes['qualification_id'] = $data['qualification_id'];
            }

            if (array_key_exists('level', $data)) {
                $attributes['level'] = $data['level'];
            }

            if ($attributes !== []) {
                $qualification->fill($attributes);
                $qualification->save();
            }

            return $qualification;
        });
    }
}
