<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QualificationLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $task_id
 * @property-read int $qualification_id
 * @property-read QualificationLevel|null $required_level
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class TaskRequirement extends Model
{
    /** @use HasFactory<\Database\Factories\TaskRequirementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'task_id',
        'qualification_id',
        'required_level',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'required_level' => QualificationLevel::class,
        ];
    }

    /**
     * @return BelongsTo<Task, $this>
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * @return BelongsTo<Qualification, $this>
     */
    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }
}
