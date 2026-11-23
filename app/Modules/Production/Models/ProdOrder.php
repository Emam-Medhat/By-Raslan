<?php

namespace App\Modules\Production\Models;

use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdOrder extends Model
{
    public const STATUSES = ['planned', 'running', 'done', 'cancelled'];

    protected $fillable = ['no', 'item_id', 'qty', 'due', 'status', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'due' => 'date'];
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProdStep::class, 'order_id');
    }
}