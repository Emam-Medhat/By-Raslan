<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Price extends Model
{
    protected $fillable = ['item_id', 'location_id', 'channel', 'price'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}