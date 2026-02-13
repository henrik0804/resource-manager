<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class StoreUserAction
{
    public function __construct(
        private StoreRoleAction $storeRole,
    ) {}

    /**
     * @param array{
     *   name: string,
     *   email: string,
     *   password: string,
     *   role_id?: int|null,
     *   role?: array{name: string, description?: string|null}|null
     * } $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            if (($data['role_id'] ?? null) !== null && array_key_exists('role', $data)) {
                throw new InvalidArgumentException('Provide either role_id or role data, not both.');
            }

            $roleId = $data['role_id'] ?? null;

            if ($roleId === null && isset($data['role'])) {
                $roleId = $this->storeRole->handle($data['role'])->id;
            }

            if ($roleId === null) {
                throw new InvalidArgumentException('Role data is required to create a user.');
            }

            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $roleId,
            ]);
        });
    }
}
