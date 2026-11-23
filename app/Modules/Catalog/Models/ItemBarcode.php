<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemBarcode extends Model
{
    protected $fillable = ['item_id', 'code'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}