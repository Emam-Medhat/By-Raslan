<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['po_id', 'line', 'item_id', 'qty', 'price', 'received'];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:3',
            'price' => 'decimal:2',
            'received' => 'decimal:3',
        ];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}