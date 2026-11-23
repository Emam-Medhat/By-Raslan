<?php

namespace App\Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;

class SalesReturnLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['return_id', 'sale_line_id', 'item_id', 'box_sn', 'qty', 'unit_price', 'total'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'unit_price' => 'decimal:2', 'total' => 'decimal:2'];
    }
}