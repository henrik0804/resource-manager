<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ResourceAbsence;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateResourceAbsenceAction
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
     *   starts_at?: \DateTimeInterface|string,
     *   ends_at?: \DateTimeInterface|string,
     *   recurrence_rule?: string|null
     * } $data
     */
    public function handle(ResourceAbsence $absence, array $data): ResourceAbsence
    {
        return DB::transaction(function () use ($absence, $data): ResourceAbsence {
            if (array_key_exists('resource_id', $data) && array_key_exists('resource', $data)) {
                throw new InvalidArgumentException('Provide either resource_id or resource data, not both.');
            }

            $attributes = [];

            if (array_key_exists('resource', $data)) {
                $attributes['resource_id'] = $this->storeResource->handle($data['resource'])->id;
            } elseif (array_key_exists('resource_id', $data)) {
                $attributes['resource_id'] = $data['resource_id'];
            }

            foreach (['starts_at', 'ends_at', 'recurrence_rule'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $data[$key];
                }
            }

            if ($attributes !== []) {
                $absence->fill($attributes);
                $absence->save();
            }

            return $absence;
        });
    }
}
