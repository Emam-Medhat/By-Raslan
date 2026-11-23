<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineOrderBox extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'box_sn', 'line', 'qty', 'removed', 'at', 'active_box'];

    protected function casts(): array
    {
        return ['removed' => 'boolean', 'qty' => 'decimal:3', 'at' => 'datetime'];
    }
}