<?php

declare(strict_types=1);

use App\Models\Qualification;
use App\Models\Resource;
use App\Models\ResourceAbsence;
use App\Models\ResourceQualification;
use App\Models\ResourceType;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\TaskRequirement;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\get;

test('guests are redirected from index pages', function (string $route): void {
    get(route($route))->assertRedirect(route('login'));
})->with([
    'resource-types.index',
    'roles.index',
    'permissions.index',
    'qualifications.index',
    'resources.index',
    'resource-absences.index',
    'resource-qualifications.index',
    'tasks.index',
    'task-requirements.index',
    'task-assignments.index',
    'users.index',
]);

describe('authenticated users', function (): void {
    beforeEach(function (): void {
        actingAsUserWithPermissions();
    });

    test('authenticated users can view the resource types index', function (): void {
        ResourceType::factory()->count(3)->create();

        get(route('resource-types.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('resource-types/Index')
                ->has('resourceTypes.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the roles index', function (): void {
        Role::factory()->count(3)->create();

        $expectedCount = Role::count();

        get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('roles/Index')
                ->has('roles.data', $expectedCount)
                ->has('search')
            );
    });

    test('authenticated users can view the permissions index', function (): void {
        get(route('permissions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('permissions/Index')
                ->has('permissions.data')
                ->has('sections')
                ->has('search')
            );
    });

    test('authenticated users can view the qualifications index', function (): void {
        Qualification::factory()->count(3)->create();

        get(route('qualifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('qualifications/Index')
                ->has('qualifications.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the resources index', function (): void {
        Resource::factory()->count(3)->create();

        get(route('resources.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('resources/Index')
                ->has('resources.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the resource absences index', function (): void {
        ResourceAbsence::factory()->count(3)->create();

        get(route('resource-absences.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('resource-absences/Index')
                ->has('resourceAbsences.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the resource qualifications index', function (): void {
        ResourceQualification::factory()->count(3)->create();

        get(route('resource-qualifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('resource-qualifications/Index')
                ->has('resourceQualifications.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the tasks index', function (): void {
        Task::factory()->count(3)->create();

        get(route('tasks.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tasks/Index')
                ->has('tasks.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the task requirements index', function (): void {
        TaskRequirement::factory()->count(3)->create();

        get(route('task-requirements.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('task-requirements/Index')
                ->has('taskRequirements.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the task assignments index', function (): void {
        TaskAssignment::factory()->count(3)->create();

        get(route('task-assignments.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('task-assignments/Index')
                ->has('taskAssignments.data', 3)
                ->has('search')
            );
    });

    test('authenticated users can view the users index', function (): void {
        get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->has('users.data')
                ->has('search')
            );
    });

    test('resource types index supports search', function (): void {
        ResourceType::factory()->create(['name' => 'Maschine']);
        ResourceType::factory()->create(['name' => 'Raum']);

        get(route('resource-types.index', ['search' => 'Maschine']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('resource-types/Index')
                ->where('search', 'Maschine')
                ->has('resourceTypes.data', 1)
            );
    });

    test('tasks index supports search', function (): void {
        Task::factory()->create(['title' => 'Wartung durchführen']);
        Task::factory()->create(['title' => 'Bericht erstellen']);

        get(route('tasks.index', ['search' => 'Wartung']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('tasks/Index')
                ->where('search', 'Wartung')
                ->has('tasks.data', 1)
            );
    });

    test('users index supports search', function (): void {
        User::factory()->create(['name' => 'Max Mustermann']);

        get(route('users.index', ['search' => 'Max']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('users/Index')
                ->where('search', 'Max')
            );
    });
});
