<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceAbsence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreResourceAbsenceAction
{
    public function __construct(
        private StoreResourceAction $storeResource,
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
     *   starts_at: \DateTimeInterface|string,
     *   ends_at: \DateTimeInterface|string,
     *   recurrence_rule?: string|null
     * } $data
     */
    public function handle(array $data): ResourceAbsence
    {
        return DB::transaction(function () use ($data): ResourceAbsence {
            if (($data['resource_id'] ?? null) !== null && array_key_exists('resource', $data)) {
                throw new InvalidArgumentException('Provide either resource_id or resource data, not both.');
            }

            $resourceId = $data['resource_id'] ?? null;

            if ($resourceId === null && isset($data['resource'])) {
                $resourceId = $this->storeResource->handle($data['resource'])->id;
            }

            if ($resourceId === null) {
                throw new InvalidArgumentException('Resource data is required to create a resource absence.');
            }

            return ResourceAbsence::create([
                'resource_id' => $resourceId,
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'recurrence_rule' => $data['recurrence_rule'] ?? null,
            ]);
        });
    }
}
