<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\AccessSection;
use App\Models\Resource as ResourceModel;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $email
 * @property-read int $role_id
 * @property-read CarbonImmutable|null $email_verified_at
 * @property-read string $password
 * @property-read string|null $remember_token
 * @property-read string|null $two_factor_secret
 * @property-read string|null $two_factor_recovery_codes
 * @property-read CarbonImmutable|null $two_factor_confirmed_at
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * @return HasOne<ResourceModel, $this>
     */
    public function resource(): HasOne
    {
        return $this->hasOne(ResourceModel::class);
    }

    public function permissionFor(AccessSection $section): ?Permission
    {
        $role = $this->role;

        if (! $role) {
            return null;
        }

        if ($role->relationLoaded('permissions')) {
            return $role->permissions->first(fn (Permission $permission) => $permission->section === $section);
        }

        return $role->permissions()->where('section', $section->value)->first();
    }

    public function canReadSection(AccessSection $section): bool
    {
        $permission = $this->permissionFor($section);

        return $permission?->can_read
            || $permission?->can_write
            || $permission?->can_write_owned;
    }

    public function canWriteSection(AccessSection $section): bool
    {
        return (bool) $this->permissionFor($section)?->can_write;
    }

    public function canWriteOwnedSection(AccessSection $section): bool
    {
        return (bool) $this->permissionFor($section)?->can_write_owned;
    }
}
