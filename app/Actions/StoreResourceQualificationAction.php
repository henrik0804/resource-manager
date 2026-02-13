<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\QualificationLevel;
use App\Models\ResourceQualification;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreResourceQualificationAction
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
    public function handle(array $data): ResourceQualification
    {
        return DB::transaction(function () use ($data): ResourceQualification {
            if (($data['resource_id'] ?? null) !== null && array_key_exists('resource', $data)) {
                throw new InvalidArgumentException('Provide either resource_id or resource data, not both.');
            }

            if (($data['qualification_id'] ?? null) !== null && array_key_exists('qualification', $data)) {
                throw new InvalidArgumentException('Provide either qualification_id or qualification data, not both.');
            }

            $resourceId = $data['resource_id'] ?? null;

            if ($resourceId === null && isset($data['resource'])) {
                $resourceId = $this->storeResource->handle($data['resource'])->id;
            }

            $qualificationId = $data['qualification_id'] ?? null;

            if ($qualificationId === null && isset($data['qualification'])) {
                $qualificationId = $this->storeQualification->handle($data['qualification'])->id;
            }

            if ($resourceId === null || $qualificationId === null) {
                throw new InvalidArgumentException('Resource and qualification data are required to create a resource qualification.');
            }

            return ResourceQualification::create([
                'resource_id' => $resourceId,
                'qualification_id' => $qualificationId,
                'level' => $data['level'] ?? null,
            ]);
        });
    }
}
