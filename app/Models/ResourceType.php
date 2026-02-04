<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string|null $description
 * @property-read Carbon|null $created_at
 * @property-read Carbon|null $updated_at
 */
class ResourceType extends Model
{
    /** @use HasFactory<\Database\Factories\ResourceTypeFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * @return HasMany<\App\Models\Resource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    /**
     * @return HasMany<Qualification, $this>
     */
    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class);
    }
}
