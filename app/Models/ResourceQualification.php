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
 * @property-read int $resource_id
 * @property-read int $qualification_id
 * @property-read QualificationLevel|null $level
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class ResourceQualification extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceQualificationFactory> */
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
     * @return BelongsTo<\App\Models\Resource, $this>
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
