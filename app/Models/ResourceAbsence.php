<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read int $resource_id
 * @property-read Carbon $starts_at
 * @property-read Carbon $ends_at
 * @property-read string|null $recurrence_rule
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class ResourceAbsence extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceAbsenceFactory> */
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
     * @return BelongsTo<\App\Models\Resource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
