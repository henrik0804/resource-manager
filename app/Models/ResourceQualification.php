<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QualificationLevel;
use App\Models\Resource as ResourceModel;
use Carbon\CarbonImmutable;
use Database\Factories\ResourceQualificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $resource_id
 * @property-read int $qualification_id
 * @property-read QualificationLevel|null $level
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class ResourceQualification extends Model
{
    /** @use HasFactory<ResourceQualificationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'resource_id',
        'qualification_id',
        'level',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => QualificationLevel::class,
        ];
    }

    /**
     * @return BelongsTo<ResourceModel, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    /**
     * @return BelongsTo<Qualification, $this>
     */
    public function qualification(): BelongsTo
    {
        return $this->belongsTo(Qualification::class);
    }
}
