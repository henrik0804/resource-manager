<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Resource as ResourceModel;
use Carbon\CarbonImmutable;
use Database\Factories\ResourceAbsenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $resource_id
 * @property-read CarbonImmutable $starts_at
 * @property-read CarbonImmutable $ends_at
 * @property-read string|null $recurrence_rule
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class ResourceAbsence extends Model
{
    /** @use HasFactory<ResourceAbsenceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resource_id',
        'starts_at',
        'ends_at',
        'recurrence_rule',
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
     * @return BelongsTo<ResourceModel, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
