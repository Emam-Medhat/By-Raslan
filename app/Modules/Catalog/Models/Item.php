<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string|null $scale_plu
 */
class Item extends Model
{
    public const KINDS = [
        'product' => 'منتج',
        'semi' => 'شبه مصنع',
        'raw' => 'خام',
        'mix' => 'مخلوط',
        'pack' => 'تعبئة',
    ];

    protected $fillable = [
        'family_id', 'name', 'kind', 'unit', 'scale_plu', 'also_families', 'tags', 'active',
    ];

    protected function casts(): array
    {
        return [
            'also_families' => 'array',
            'tags' => 'array',
            'active' => 'boolean',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function routes()
    {
        return $this->hasMany(\App\Modules\Production\Models\ItemRoute::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ItemBarcode::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}