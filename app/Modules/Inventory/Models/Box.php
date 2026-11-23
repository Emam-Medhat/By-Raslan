<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $sn
 * @property string $status
 */
class Box extends Model
{
    protected $primaryKey = 'sn';

    public $incrementing = true;

    protected $keyType = 'int';

    public const STATUSES = ['stock', 'loaded', 'sold', 'waste', 'donated'];

    protected $fillable = [
        'item_id', 'weight', 'scale_code', 'prod_order_id', 'packed_by', 'packed_at',
        'expires_at', 'location_id', 'status', 'crate',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'packed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function moves(): HasMany
    {
        return $this->hasMany(StockMove::class, 'box_sn', 'sn');
    }
}