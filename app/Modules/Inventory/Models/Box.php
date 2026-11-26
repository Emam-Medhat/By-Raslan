<?php

namespace App\Modules\Inventory\Models;

use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Box extends Model
{
    protected $primaryKey = 'sn';

    public $incrementing = true;

    protected $keyType = 'int';

    public const STATUSES = ['stock', 'loaded', 'sold', 'waste', 'donated', 'missing', 'transit'];

    protected $fillable = [
        'uid', 'item_id', 'weight', 'scale_code', 'prod_order_id', 'packed_by', 'packed_at', 'produced_at',
        'expires_at', 'location_id', 'source_location_id', 'status', 'sold_at', 'sale_id', 'returned_at', 'crate', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:3',
            'packed_at' => 'datetime',
            'produced_at' => 'datetime',
            'expires_at' => 'datetime',
            'sold_at' => 'datetime',
            'returned_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function item(): BelongsTo { return $this->belongsTo(Item::class); }
    public function moves(): HasMany { return $this->hasMany(StockMove::class, 'box_sn', 'sn'); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class, 'location_id'); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class, 'sale_id'); }
}
