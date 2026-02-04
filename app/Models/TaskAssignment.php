<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $task_id
 * @property-read int $resource_id
 * @property-read Carbon|null $starts_at
 * @property-read Carbon|null $ends_at
 * @property-read string|null $allocation_ratio
 * @property-read string $assignment_source
 * @property-read string|null $assignee_status
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class TaskAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\TaskAssignmentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'task_id',
        'resource_id',
        'starts_at',
        'ends_at',
        'allocation_ratio',
        'assignment_source',
        'assignee_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
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
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
