<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ResourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read int $resource_type_id
 * @property-read string|null $capacity_value
 * @property-read string|null $capacity_unit
 * @property-read int|null $user_id
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class Resource extends Model
{
    /** @use HasFactory<ResourceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'resource_type_id',
        'capacity_value',
        'capacity_unit',
        'user_id',
    ];

    /**
     * @return BelongsTo<ResourceType, $this>
     */
    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ResourceQualification, $this>
     */
    public function resourceQualifications(): HasMany
    {
        return $this->hasMany(ResourceQualification::class);
    }

    /**
     * @return HasMany<TaskAssignment, $this>
     */
    public function taskAssignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class);
    }

    /**
     * @return HasMany<ResourceAbsence, $this>
     */
    public function resourceAbsences(): HasMany
    {
        return $this->hasMany(ResourceAbsence::class);
    }
}
