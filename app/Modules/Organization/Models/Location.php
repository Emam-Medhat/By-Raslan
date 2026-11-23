<?php

namespace App\Modules\Organization\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}