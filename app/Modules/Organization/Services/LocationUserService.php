<?php

namespace App\Modules\Organization\Services;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use App\Modules\Organization\Models\LocationUser;
use Illuminate\Support\Facades\DB;

class LocationUserService
{
    /**
     * Assign a user to a location with a specific role and optional permissions.
     */
    public function assign(User $user, Location $location, string $role, array $permissions = [], bool $isPrimary = false): LocationUser
    {
        return DB::transaction(function () use ($user, $location, $role, $permissions, $isPrimary) {
            // If this user is being set as primary, remove primary from others
            if ($isPrimary) {
                LocationUser::where('location_id', $location->id)
                    ->where('user_id', '!=', $user->id)
                    ->update(['is_primary' => false]);
            }

            return LocationUser::updateOrCreate(
                ['location_id' => $location->id, 'user_id' => $user->id],
                [
                    'role' => $role,
                    'is_primary' => $isPrimary,
                    'permissions' => $permissions,
                    'assigned_by' => auth()->id(),
                ]
            );
        });
    }

    /**
     * Remove a user from a location.
     */
    public function remove(Location $location, User $user): void
    {
        DB::transaction(function () use ($location, $user) {
            LocationUser::where('location_id', $location->id)
                ->where('user_id', $user->id)
                ->delete();
        });
    }

    /**
     * Update user's role and permissions in a location.
     */
    public function updateRole(LocationUser $assignment, string $role, array $permissions = [], bool $isPrimary = false): LocationUser
    {
        return DB::transaction(function () use ($assignment, $role, $permissions, $isPrimary) {
            if ($isPrimary) {
                LocationUser::where('location_id', $assignment->location_id)
                    ->where('id', '!=', $assignment->id)
                    ->update(['is_primary' => false]);
            }

            $assignment->update([
                'role' => $role,
                'is_primary' => $isPrimary,
                'permissions' => $permissions,
            ]);

            return $assignment;
        });
    }

    /**
     * Get all users for a location with their assignment details.
     */
    public function getLocationUsers(Location $location)
    {
        return LocationUser::with('user')
            ->where('location_id', $location->id)
            ->orderByDesc('is_primary')
            ->orderBy('role')
            ->get();
    }

    /**
     * Check if a user has a specific role in a location.
     */
    public function userHasRole(User $user, Location $location, string $role): bool
    {
        return LocationUser::where('location_id', $location->id)
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->exists();
    }

    /**
     * Get the primary manager for a location.
     */
    public function getPrimaryManager(Location $location): ?User
    {
        $assignment = LocationUser::with('user')
            ->where('location_id', $location->id)
            ->where('is_primary', true)
            ->first();

        return $assignment?->user;
    }
}