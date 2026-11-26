<?php

namespace App\Modules\Orders\Models;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Delivery driver, linked to exactly one branch (location_id).
 * user_id is an optional link to a staff account; drivers are chosen
 * by branch, so a driver without a user account is still usable.
 */
class Driver extends Model
{
    protected $fillable = ['user_id', 'name', 'phone', 'location_id', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForLocation($query, string $locationId)
    {
        return $query->where('location_id', $locationId)->where('active', true)->orderBy('name');
    }
}
