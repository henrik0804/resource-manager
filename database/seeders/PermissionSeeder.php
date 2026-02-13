<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AccessSection;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = AccessSection::cases();

        $rolePermissions = [
            'Admin' => [
                'read' => $sections,
                'write' => $sections,
                'write_owned' => [],
            ],
            'Planner' => [
                'read' => [
                    AccessSection::ResourceManagement,
                    AccessSection::TaskCreation,
                    AccessSection::ManualAssignment,
                    AccessSection::AutomatedAssignment,
                    AccessSection::ConflictWarning,
                    AccessSection::ConflictResolution,
                    AccessSection::PriorityScheduling,
                    AccessSection::VisualOverview,
                    AccessSection::UtilizationView,
                ],
                'write' => [
                    AccessSection::ResourceManagement,
                    AccessSection::TaskCreation,
                    AccessSection::ManualAssignment,
                    AccessSection::AutomatedAssignment,
                    AccessSection::ConflictWarning,
                    AccessSection::ConflictResolution,
                    AccessSection::PriorityScheduling,
                    AccessSection::VisualOverview,
                    AccessSection::UtilizationView,
                ],
                'write_owned' => [],
            ],
            'Manager' => [
                'read' => [
                    AccessSection::ResourceManagement,
                    AccessSection::TaskCreation,
                    AccessSection::ManualAssignment,
                    AccessSection::ConflictWarning,
                    AccessSection::ConflictResolution,
                    AccessSection::PriorityScheduling,
                    AccessSection::VisualOverview,
                    AccessSection::UtilizationView,
                ],
                'write' => [],
                'write_owned' => [],
            ],
            'Contributor' => [
                'read' => [
                    AccessSection::EmployeeFeedback,
                ],
                'write' => [],
                'write_owned' => [
                    AccessSection::EmployeeFeedback,
                ],
            ],
            'Viewer' => [
                'read' => [
                    AccessSection::ResourceManagement,
                    AccessSection::TaskCreation,
                    AccessSection::ManualAssignment,
                    AccessSection::VisualOverview,
                    AccessSection::UtilizationView,
                ],
                'write' => [],
                'write_owned' => [],
            ],
        ];

        $roles = Role::query()->get();

        foreach ($roles as $role) {
            $config = $rolePermissions[$role->name] ?? [
                'read' => [],
                'write' => [],
                'write_owned' => [],
            ];

            foreach ($sections as $section) {
                $canWrite = in_array($section, $config['write'], true);
                $canWriteOwned = in_array($section, $config['write_owned'], true);
                $canRead = $canWrite || $canWriteOwned || in_array($section, $config['read'], true);

                Permission::query()->updateOrCreate(
                    ['role_id' => $role->id, 'section' => $section->value],
                    [
                        'can_read' => $canRead,
                        'can_write' => $canWrite,
                        'can_write_owned' => $canWriteOwned,
                    ],
                );
            }
        }
    }
}
