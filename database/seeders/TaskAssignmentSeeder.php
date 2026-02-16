<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AssigneeStatus;
use App\Enums\AssignmentSource;
use App\Enums\TaskStatus;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Task;
use App\Models\TaskAssignment;
use Illuminate\Database\Seeder;

class TaskAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Each task receives a deliberate mix of hourly (Person) and slot-based
     * (Team / Room / Equipment) resource assignments to produce realistic
     * scheduling data with natural overlaps and conflicts.
     */
    public function run(): void
    {
        $personTypeId = ResourceType::query()->where('name', 'Person')->value('id');

        if ($personTypeId === null) {
            return;
        }

        $tasks = Task::query()->get()->keyBy('title');
        $persons = Resource::query()->where('resource_type_id', $personTypeId)->get()->values();
        $named = Resource::query()->where('resource_type_id', '!=', $personTypeId)->get()->keyBy('name');

        if ($tasks->isEmpty() || $persons->isEmpty()) {
            return;
        }

        /**
         * Assignment plans keyed by task title.
         *
         * Each entry is either a person assignment (`person` index into $persons)
         * or a named resource (`name` matching the resource name).
         *
         * Optional keys:
         *   - ratio: allocation_ratio (default 1.0)
         *   - from:  day offset from task start (default 0)
         *   - to:    day offset from task start (omit for full duration)
         *
         * @var array<string, list<array{person?: int, name?: string, ratio?: float, from?: int, to?: int}>>
         */
        $plans = [
            'Office Wing Renovation' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 1],
                ['person' => 2, 'from' => 1, 'to' => 3],
                ['name' => 'Workshop Bay'],
                ['name' => 'Forklift #2', 'to' => 2],
            ],
            'v3.0 Product Launch Sprint' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 3],
                ['person' => 4],
                ['person' => 5, 'ratio' => 0.75, 'to' => 3],
                ['name' => 'Design Team', 'to' => 3],
                ['name' => 'Conference Room A', 'to' => 1],
            ],
            'Annual Safety Audit' => [
                ['person' => 0],
                ['person' => 2, 'ratio' => 0.50],
                ['name' => 'Conference Room A', 'to' => 0],
                ['name' => 'Workshop Bay', 'from' => 1],
                ['name' => 'Forklift #2', 'from' => 1, 'to' => 1],
            ],
            'Client Discovery Workshop — Meridian Corp' => [
                ['person' => 1],
                ['person' => 0, 'ratio' => 0.50],
                ['name' => 'Conference Room A'],
                ['name' => 'Projector Unit'],
            ],
            'Warehouse Inventory Reconciliation' => [
                ['person' => 2],
                ['person' => 3, 'to' => 1],
                ['person' => 5, 'from' => 2],
                ['name' => 'Operations Team'],
                ['name' => 'Forklift #2'],
                ['name' => 'Workshop Bay'],
            ],
            'New Hire Onboarding — Q1 Cohort' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 4, 'ratio' => 0.25, 'from' => 1],
                ['name' => 'Conference Room A', 'to' => 2],
                ['name' => 'Meeting Room B', 'from' => 3],
                ['name' => 'Operations Team', 'from' => 3, 'to' => 3],
            ],
            'Trade Show Booth Fabrication' => [
                ['person' => 1],
                ['person' => 5, 'ratio' => 0.75],
                ['person' => 4, 'ratio' => 0.50, 'from' => 3],
                ['name' => 'Design Team'],
                ['name' => '3D Printers', 'from' => 1, 'to' => 4],
                ['name' => 'Workshop Bay', 'from' => 3],
            ],
            'IT Infrastructure Migration' => [
                ['person' => 3],
                ['person' => 4],
                ['person' => 2, 'from' => 4],
                ['name' => 'Operations Team'],
            ],
            'Quarterly Business Review Preparation' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 1, 'ratio' => 0.50],
                ['name' => 'Conference Room A', 'from' => 1],
                ['name' => 'Projector Unit', 'from' => 1],
            ],
            'Equipment Maintenance Window' => [
                ['person' => 2],
                ['person' => 5, 'ratio' => 0.50, 'to' => 1],
                ['name' => 'Forklift #2'],
                ['name' => '3D Printers'],
                ['name' => 'Workshop Bay'],
            ],
            'Cross-Team Process Optimization' => [
                ['person' => 0, 'ratio' => 0.75],
                ['person' => 1, 'ratio' => 0.50],
                ['name' => 'Design Team', 'to' => 1],
                ['name' => 'Operations Team', 'from' => 2],
                ['name' => 'Conference Room A'],
            ],
            'Emergency Drill Coordination' => [
                ['person' => 0],
                ['person' => 2],
                ['name' => 'Conference Room A'],
                ['name' => 'Workshop Bay'],
                ['name' => 'Meeting Room B'],
            ],
        ];

        foreach ($plans as $taskTitle => $entries) {
            $task = $tasks->get($taskTitle);

            if (! $task) {
                continue;
            }

            [$defaultSource, $defaultStatus] = $this->deriveAssignmentDefaults($task->status);

            foreach ($entries as $entry) {
                $resource = isset($entry['person'])
                    ? $persons->get($entry['person'])
                    : $named->get($entry['name']);

                if (! $resource) {
                    continue;
                }

                $startsAt = $task->starts_at->copy()->addDays($entry['from'] ?? 0);
                $endsAt = array_key_exists('to', $entry)
                    ? $task->starts_at->copy()->addDays($entry['to'])->setTime(17, 0)
                    : $task->ends_at;

                TaskAssignment::query()->create([
                    'task_id' => $task->id,
                    'resource_id' => $resource->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'allocation_ratio' => $entry['ratio'] ?? 1.00,
                    'assignment_source' => $defaultSource,
                    'assignee_status' => $defaultStatus,
                ]);
            }
        }
    }

    /**
     * Derive sensible assignment_source and assignee_status from the parent task status.
     *
     * @return array{AssignmentSource, AssigneeStatus}
     */
    private function deriveAssignmentDefaults(TaskStatus $taskStatus): array
    {
        return match ($taskStatus) {
            TaskStatus::Done => [AssignmentSource::Manual, AssigneeStatus::Done],
            TaskStatus::InProgress => [AssignmentSource::Manual, AssigneeStatus::InProgress],
            TaskStatus::Blocked => [AssignmentSource::Manual, AssigneeStatus::Pending],
            TaskStatus::Planned => [
                fake()->randomElement(AssignmentSource::cases()),
                fake()->randomElement([AssigneeStatus::Pending, AssigneeStatus::Accepted]),
            ],
        };
    }
}
