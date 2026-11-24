<?php

namespace App\Models;

use App\Modules\Identity\Models\Permission;
    use App\Modules\Identity\Models\Role;
    use App\Modules\Organization\Models\Location;
    use Illuminate\Database\Eloquent\Concerns\HasUuids;
    use Illuminate\Database\Eloquent\Factories\HasFactory;
    use Illuminate\Database\Eloquent\Relations\BelongsToMany;
    use Illuminate\Foundation\Auth\User as Authenticatable;
    use Illuminate\Notifications\Notifiable;

/**
 * Staff user (Identity aggregate root).
 *
 * @property string $id
 * @property bool   $is_owner
 * @property bool   $active
 * @property string|null $home_location_id
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'is_owner',
        'active',
        'home_location_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_owner' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /** Direct grants (permission_user pivot carries until + max_amount). */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)
            ->withPivot(['until', 'max_amount', 'granted_by'])
            ->withTimestamps();
    }

    /** Locations this user is assigned to (with role + per-location permissions). */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_user')
            ->withPivot(['role', 'is_primary', 'permissions', 'assigned_by'])
            ->withTimestamps();
    }

    public function isOwner(): bool
    {
        return (bool) $this->is_owner;
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /** Effective permissions = role permissions + non-expired direct grants. */
    public function effectivePermissions(): \Illuminate\Support\Collection
    {
        $rolePerms = $this->roles()->with('permissions')->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('id'));

        $directPerms = $this->permissions()
            ->where(fn ($q) => $q->whereNull('until')->orWhere('until', '>=', now()->toDateString()))
            ->pluck('permissions.id');

        return $rolePerms->merge($directPerms)->unique()->values();
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isOwner()) {
            return true;
        }

        return $this->effectivePermissions()->contains($permission);
    }

    /** Amount limit for a permission (null = unlimited for owner). */
    public function permissionLimit(string $permission): ?float
    {
        if ($this->isOwner()) {
            return null;
        }

        $grant = $this->permissions()
            ->where('permissions.id', $permission)
            ->where(fn ($q) => $q->whereNull('until')->orWhere('until', '>=', now()->toDateString()))
            ->first();

        return $grant ? (float) $grant->pivot->max_amount : null;
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}