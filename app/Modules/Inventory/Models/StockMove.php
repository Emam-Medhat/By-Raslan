<?php

namespace App\Modules\Inventory\Models;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only stock ledger entry. Corrections are made with a reversing
 * entry, never by editing or deleting an existing row.
 *
 * @property float  $qty
 */
class StockMove extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'at';

    public const KINDS = ['pack', 'receive', 'sale', 'transfer', 'adjust', 'waste', 'donate', 'count', 'return', 'production'];

    protected $fillable = [
        'item_id', 'location_id', 'qty', 'kind', 'reason', 'ref', 'box_sn', 'unit_cost', 'by',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_sn', 'sn');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'by');
    }
}