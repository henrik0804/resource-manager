<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QualificationLevel;
use App\Models\Qualification;
use App\Models\Task;
use App\Models\TaskRequirement;
use Illuminate\Database\Seeder;

class TaskRequirementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Maps qualifications to tasks based on logical relevance rather than
     * random selection, so the test data tells a coherent story.
     */
    public function run(): void
    {
        $tasks = Task::query()->get()->keyBy('title');
        $qualifications = Qualification::query()->get()->keyBy('name');

        if ($tasks->isEmpty() || $qualifications->isEmpty()) {
            return;
        }

        /**
         * Requirement plans keyed by task title.
         *
         * Each entry specifies a qualification name and the minimum required level.
         *
         * @var array<string, list<array{qualification: string, level: QualificationLevel}>>
         */
        $plans = [
            'Büroflügel Renovierung' => [
                ['qualification' => 'Sicherheitsschulung', 'level' => QualificationLevel::Intermediate],
                ['qualification' => 'Projektmanagement', 'level' => QualificationLevel::Advanced],
            ],
            'Produktversion 3.0 Start sprint' => [
                ['qualification' => 'Frontend-Entwicklung', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'Backend-Entwicklung', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'UX-Forschung', 'level' => QualificationLevel::Intermediate],
            ],
            'Jährliche Sicherheitsprüfung' => [
                ['qualification' => 'Sicherheitsschulung', 'level' => QualificationLevel::Expert],
                ['qualification' => 'Gabelstapler-Führerschein', 'level' => QualificationLevel::Advanced],
            ],
            'Kunden-Workshop — Meridian Corp' => [
                ['qualification' => 'Workshop-Moderation', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'UX-Forschung', 'level' => QualificationLevel::Intermediate],
            ],
            'Lagerbestands-Ausgleich' => [
                ['qualification' => 'Sicherheitsschulung', 'level' => QualificationLevel::Beginner],
                ['qualification' => 'Gabelstapler-Führerschein', 'level' => QualificationLevel::Intermediate],
            ],
            'Einarbeitung neue Mitarbeiter — Q1 Kohorte' => [
                ['qualification' => 'Projektmanagement', 'level' => QualificationLevel::Intermediate],
            ],
            'Messestand Fertigung' => [
                ['qualification' => 'Frontend-Entwicklung', 'level' => QualificationLevel::Beginner],
                ['qualification' => 'UX-Forschung', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'Audio-Einrichtung', 'level' => QualificationLevel::Intermediate],
            ],
            'IT-Infrastruktur Migration' => [
                ['qualification' => 'Backend-Entwicklung', 'level' => QualificationLevel::Expert],
            ],
            'Quartalsreview Vorbereitung' => [
                ['qualification' => 'Projektmanagement', 'level' => QualificationLevel::Beginner],
            ],
            'Wartungsfenster Ausstattung' => [
                ['qualification' => 'Sicherheitsschulung', 'level' => QualificationLevel::Intermediate],
                ['qualification' => 'Gabelstapler-Führerschein', 'level' => QualificationLevel::Advanced],
            ],
            'Prozessoptimierung Team-übergreifend' => [
                ['qualification' => 'Projektmanagement', 'level' => QualificationLevel::Advanced],
                ['qualification' => 'Workshop-Moderation', 'level' => QualificationLevel::Intermediate],
            ],
            'Notfallübung Koordination' => [
                ['qualification' => 'Sicherheitsschulung', 'level' => QualificationLevel::Expert],
            ],
        ];

        foreach ($plans as $taskTitle => $entries) {
            $task = $tasks->get($taskTitle);

            if (! $task) {
                continue;
            }

            foreach ($entries as $entry) {
                $qualification = $qualifications->get($entry['qualification']);

                if (! $qualification) {
                    continue;
                }

                TaskRequirement::query()->create([
                    'task_id' => $task->id,
                    'qualification_id' => $qualification->id,
                    'required_level' => $entry['level'],
                ]);
            }
        }
    }
}
