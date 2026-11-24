<?php

namespace App\Modules\Organization\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 */
class Location extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['id', 'name', 'type', 'parent_id', 'city', 'active', 'sort'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public const TYPES = [
        'factory' => 'مصنع',
        'section' => 'قسم',
        'warehouse' => 'مخزن',
        'sub' => 'مخزن فرعي',
        'dark' => 'دارك ستور',
        'shop' => 'فرع',
        'partner' => 'مخزن شريك',
        'donation' => 'مخزن تبرعات',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    /** Users assigned to this location (with role + per-location permissions). */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'location_user', 'location_id', 'user_id')
            ->withPivot(['role', 'is_primary', 'permissions', 'assigned_by'])
            ->withTimestamps();
    }

    /** Primary manager for this location. */
    public function primaryManager()
    {
        return $this->users()->wherePivot('is_primary', true)->limit(1);
    }
}