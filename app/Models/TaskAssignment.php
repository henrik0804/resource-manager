<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Resource as ResourceModel;
use Carbon\CarbonImmutable;
use Database\Factories\TaskAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $task_id
 * @property-read int $resource_id
 * @property-read CarbonImmutable|null $starts_at
 * @property-read CarbonImmutable|null $ends_at
 * @property-read string|null $allocation_ratio
 * @property-read string $assignment_source
 * @property-read string|null $assignee_status
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class TaskAssignment extends Model
{
    /** @use HasFactory<TaskAssignmentFactory> */
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
     * @return BelongsTo<ResourceModel, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(ResourceModel::class);
    }

    public function isOwnedBy(User $user): bool
    {
        if ($this->relationLoaded('resource') && $this->resource) {
            return $this->resource->user_id === $user->id;
        }

        return $this->resource()->where('user_id', $user->id)->exists();
    }
}
