<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\MapsToTimelineBar;
use App\Enums\AssigneeStatus;
use App\Enums\AssignmentSource;
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
 * @property-read AssignmentSource $assignment_source
 * @property-read AssigneeStatus|null $assignee_status
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class TaskAssignment extends Model implements MapsToTimelineBar
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
            'assignment_source' => AssignmentSource::class,
            'assignee_status' => AssigneeStatus::class,
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

    /**
     * @return array{start: string, end: string, ganttBarConfig: array{id: string, label: string, style: array<string, string>}}
     */
    public function toTimelineBar(): array
    {
        $startsAt = $this->starts_at ?? $this->task?->starts_at;
        $endsAt = $this->ends_at ?? $this->task?->ends_at;

        return [
            'start' => $startsAt?->format('Y-m-d H:i') ?? '',
            'end' => $endsAt?->format('Y-m-d H:i') ?? '',
            'ganttBarConfig' => [
                'id' => "assignment-{$this->id}",
                'label' => $this->task?->title ?? "Zuweisung #{$this->id}",
                'style' => [
                    'background' => '#3b82f6',
                    'color' => 'white',
                    'borderRadius' => '4px',
                ],
            ],
        ];
    }
}
