<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\QualificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read int|null $resource_type_id
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class Qualification extends Model
{
    /** @use HasFactory<QualificationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'resource_type_id',
    ];

    /**
     * @return BelongsTo<ResourceType, $this>
     */
    public function resourceType(): BelongsTo
    {
        return $this->belongsTo(ResourceType::class);
    }

    /**
     * @return HasMany<ResourceQualification, $this>
     */
    public function resourceQualifications(): HasMany
    {
        return $this->hasMany(ResourceQualification::class);
    }

    /**
     * @return HasMany<TaskRequirement, $this>
     */
    public function taskRequirements(): HasMany
    {
        return $this->hasMany(TaskRequirement::class);
    }
}
