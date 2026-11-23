<?php

namespace App\Modules\Orders\Models;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineOrderLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'line', 'item_id', 'qty', 'unit_price', 'prepared'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'unit_price' => 'decimal:2', 'prepared' => 'decimal:3'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}