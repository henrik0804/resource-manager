<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Role;
use App\Models\User;

test('permission checks return false without permissions', function (): void {
    $role = Role::factory()->create();
    $user = User::factory()->create(['role_id' => $role->id]);

    expect($user->canReadSection(AccessSection::ConflictWarning))->toBeFalse();
    expect($user->canWriteSection(AccessSection::ConflictWarning))->toBeFalse();
    expect($user->canWriteOwnedSection(AccessSection::ConflictWarning))->toBeFalse();
});
