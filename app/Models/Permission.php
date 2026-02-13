<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessSection;
use Carbon\CarbonImmutable;
use Database\Factories\PermissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read int $id
 * @property-read int $role_id
 * @property-read AccessSection $section
 * @property-read bool $can_read
 * @property-read bool $can_write
 * @property-read bool $can_write_owned
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class Permission extends Model
{
    /** @use HasFactory<PermissionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'section',
        'can_read',
        'can_write',
        'can_write_owned',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'section' => AccessSection::class,
            'can_read' => 'bool',
            'can_write' => 'bool',
            'can_write_owned' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
