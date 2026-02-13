<?php

declare(strict_types=1);

use App\Enums\AccessSection;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/**
 * @param  array{read?: array<AccessSection>, write?: array<AccessSection>, write_owned?: array<AccessSection>}  $config
 */
function createRoleWithPermissions(array $config = []): Role
{
    $role = Role::factory()->create();
    $sections = AccessSection::cases();

    $readSections = $config['read'] ?? $sections;
    $writeSections = $config['write'] ?? $sections;
    $writeOwnedSections = $config['write_owned'] ?? [];

    foreach ($sections as $section) {
        $canWrite = in_array($section, $writeSections, true);
        $canWriteOwned = in_array($section, $writeOwnedSections, true);
        $canRead = $canWrite || $canWriteOwned || in_array($section, $readSections, true);

        Permission::query()->create([
            'role_id' => $role->id,
            'section' => $section,
            'can_read' => $canRead,
            'can_write' => $canWrite,
            'can_write_owned' => $canWriteOwned,
        ]);
    }

    return $role;
}

/**
 * @param  array{read?: array<AccessSection>, write?: array<AccessSection>, write_owned?: array<AccessSection>}  $config
 */
function actingAsUserWithPermissions(array $config = []): User
{
    $role = createRoleWithPermissions($config);
    $user = User::factory()->create(['role_id' => $role->id]);

    actingAs($user);

    return $user;
}
