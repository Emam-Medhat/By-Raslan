<?php

namespace App\Modules\Returns\Models;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['return_id', 'item_id', 'qty', 'cost'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'cost' => 'decimal:2'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}