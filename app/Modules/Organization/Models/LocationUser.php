<?php

namespace App\Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot model linking users to locations with role and per-location permissions.
 * Mirrors Nano City's BranchUser model.
 */
class LocationUser extends Model
{
    protected $table = 'location_user';

    protected $fillable = [
        'location_id',
        'user_id',
        'role',
        'is_primary',
        'permissions',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public const ROLES = ['owner' => 'مالك', 'manager' => 'مدير', 'cashier' => 'كاشير', 'staff' => 'موظف'];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}