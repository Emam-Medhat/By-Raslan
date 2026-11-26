<?php

namespace App\Modules\Orders\Models;

use App\Modules\Inventory\Models\Box;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineOrderBox extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'box_sn', 'line', 'qty', 'removed', 'at', 'active_box'];

    protected function casts(): array
    {
        return ['removed' => 'boolean', 'qty' => 'decimal:3', 'at' => 'datetime'];
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'box_sn', 'sn');
    }
}
