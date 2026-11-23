<?php

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Identity\Models\Permission;
use App\Shared\Support\Result;

/**
 * Central authorization brain for the Identity & Access module.
 *
 * Mirrors the legacy `has_perm` / `perm_limit` / `secure_grant` semantics:
 *  - owner bypasses everything
 *  - a permission can be time-bound (until) and amount-bound (max_amount)
 *  - granting is anti-escalation: you cannot grant a permission you do not
 *    hold, and you cannot grant the dangerous `users.manage` permission
 *    unless you already hold it.
 */
class PermissionService
{
    public function can(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    public function limit(User $user, string $permission): ?float
    {
        return $user->permissionLimit($permission);
    }

    /** @return list<string> */
    public function effective(User $user): array
    {
        if ($user->isOwner()) {
            return Permission::query()->pluck('id')->all();
        }

        return $user->effectivePermissions()->all();
    }

    /**
     * Grant a permission directly to a user, enforcing anti-escalation.
     */
    public function grant(User $actor, User $user, string $permission, ?string $until = null, ?float $maxAmount = null): Result
    {
        if (! $this->can($actor, 'users.manage')) {
            return Result::fail('لا تملك صلاحية إدارة المستخدمين');
        }

        // Anti-escalation: cannot grant what you don't have (except owner).
        if (! $actor->isOwner() && ! $this->can($actor, $permission)) {
            return Result::fail('لا يمكنك منح صلاحية لا تملكها');
        }

        if (! $actor->isOwner() && $permission === 'users.manage') {
            return Result::fail('لا يمكنك منح صلاحية إدارة المستخدمين');
        }

        $user->permissions()->syncWithoutDetaching([
            $permission => ['until' => $until, 'max_amount' => $maxAmount, 'granted_by' => $actor->id],
        ]);

        return Result::success();
    }

    public function revoke(User $actor, User $user, string $permission): Result
    {
        if (! $this->can($actor, 'users.manage')) {
            return Result::fail('لا تملك صلاحية إدارة المستخدمين');
        }

        $user->permissions()->detach($permission);

        return Result::success();
    }
}