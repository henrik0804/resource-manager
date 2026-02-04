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
     */
    public function run(): void
    {
        $tasks = Task::query()->get();
        $qualifications = Qualification::query()->get();

        if ($tasks->isEmpty() || $qualifications->isEmpty()) {
            return;
        }

        $levels = QualificationLevel::cases();
        foreach ($tasks as $task) {
            $count = min(2, $qualifications->count());
            $selection = collect($qualifications->random(fake()->numberBetween(1, $count)));

            foreach ($selection as $qualification) {
                TaskRequirement::query()->create([
                    'task_id' => $task->id,
                    'qualification_id' => $qualification->id,
                    'required_level' => fake()->optional()->randomElement($levels),
                ]);
            }
        }
    }
}
