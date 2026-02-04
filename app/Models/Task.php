<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $title
 * @property-read string|null $description
 * @property-read Carbon $starts_at
 * @property-read Carbon $ends_at
 * @property-read string $effort_value
 * @property-read string $effort_unit
 * @property-read string $priority
 * @property-read string $status
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'starts_at',
        'ends_at',
        'effort_value',
        'effort_unit',
        'priority',
        'status',
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
     * @return HasMany<TaskRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(TaskRequirement::class);
    }

    /**
     * @return HasMany<TaskAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }
}
