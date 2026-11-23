<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemSpecVersion extends Model
{
    protected $fillable = ['item_id', 'version', 'snapshot', 'approved_by', 'approved_at'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'approved_at' => 'datetime'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Catalog\Models\Item::class);
    }
}