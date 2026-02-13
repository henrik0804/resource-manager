<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateUserAction
{
    public function __construct(
        private StoreRoleAction $storeRole,
    ) {}

    /**
     * @param array{
     *   name?: string,
     *   email?: string,
     *   password?: string,
     *   role_id?: int|null,
     *   role?: array{name: string, description?: string|null}|null
     * } $data
     */
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            if (array_key_exists('role_id', $data) && array_key_exists('role', $data)) {
                throw new InvalidArgumentException('Provide either role_id or role data, not both.');
            }

            $attributes = [];

            foreach (['name', 'email', 'password'] as $key) {
                if (array_key_exists($key, $data)) {
                    $attributes[$key] = $data[$key];
                }
            }

            if (array_key_exists('role', $data)) {
                $attributes['role_id'] = $this->storeRole->handle($data['role'])->id;
            } elseif (array_key_exists('role_id', $data)) {
                $attributes['role_id'] = $data['role_id'];
            }

            if ($attributes !== []) {
                $user->fill($attributes);
                $user->save();
            }

            return $user;
        });
    }
}
