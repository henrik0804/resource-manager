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
            'Büroflügel Renovierung' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 1],
                ['person' => 2, 'from' => 1, 'to' => 3],
                ['name' => 'Werkstattbereich'],
                ['name' => 'Gabelstapler #2', 'to' => 2],
            ],
            'Produktversion 3.0 Start sprint' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 3],
                ['person' => 4],
                ['person' => 5, 'ratio' => 0.75, 'to' => 3],
                ['name' => 'Entwicklungsteam', 'to' => 3],
                ['name' => 'Konferenzraum A', 'to' => 1],
            ],
            'Jährliche Sicherheitsprüfung' => [
                ['person' => 0],
                ['person' => 2, 'ratio' => 0.50],
                ['name' => 'Konferenzraum A', 'to' => 0],
                ['name' => 'Werkstattbereich', 'from' => 1],
                ['name' => 'Gabelstapler #2', 'from' => 1, 'to' => 1],
            ],
            'Kunden-Workshop — Meridian Corp' => [
                ['person' => 1],
                ['person' => 0, 'ratio' => 0.50],
                ['name' => 'Konferenzraum A'],
                ['name' => 'Beamer'],
            ],
            'Lagerbestands-Ausgleich' => [
                ['person' => 2],
                ['person' => 3, 'to' => 1],
                ['person' => 5, 'from' => 2],
                ['name' => 'Betriebsteam'],
                ['name' => 'Gabelstapler #2'],
                ['name' => 'Werkstattbereich'],
            ],
            'Einarbeitung neue Mitarbeiter — Q1 Kohorte' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 4, 'ratio' => 0.25, 'from' => 1],
                ['name' => 'Konferenzraum A', 'to' => 2],
                ['name' => 'Besprechungsraum B', 'from' => 3],
                ['name' => 'Betriebsteam', 'from' => 3, 'to' => 3],
            ],
            'Messestand Fertigung' => [
                ['person' => 1],
                ['person' => 5, 'ratio' => 0.75],
                ['person' => 4, 'ratio' => 0.50, 'from' => 3],
                ['name' => 'Entwicklungsteam'],
                ['name' => '3D-Drucker', 'from' => 1, 'to' => 4],
                ['name' => 'Werkstattbereich', 'from' => 3],
            ],
            'IT-Infrastruktur Migration' => [
                ['person' => 3],
                ['person' => 4],
                ['person' => 2, 'from' => 4],
                ['name' => 'Betriebsteam'],
            ],
            'Quartalsreview Vorbereitung' => [
                ['person' => 0, 'ratio' => 0.50],
                ['person' => 1, 'ratio' => 0.50],
                ['name' => 'Konferenzraum A', 'from' => 1],
                ['name' => 'Beamer', 'from' => 1],
            ],
            'Wartungsfenster Ausstattung' => [
                ['person' => 2],
                ['person' => 5, 'ratio' => 0.50, 'to' => 1],
                ['name' => 'Gabelstapler #2'],
                ['name' => '3D-Drucker'],
                ['name' => 'Werkstattbereich'],
            ],
            'Prozessoptimierung Team-übergreifend' => [
                ['person' => 0, 'ratio' => 0.75],
                ['person' => 1, 'ratio' => 0.50],
                ['name' => 'Entwicklungsteam', 'to' => 1],
                ['name' => 'Betriebsteam', 'from' => 2],
                ['name' => 'Konferenzraum A'],
            ],
            'Notfallübung Koordination' => [
                ['person' => 0],
                ['person' => 2],
                ['name' => 'Konferenzraum A'],
                ['name' => 'Werkstattbereich'],
                ['name' => 'Besprechungsraum B'],
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
