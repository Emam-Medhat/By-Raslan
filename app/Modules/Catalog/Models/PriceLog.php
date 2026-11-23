<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;

class PriceLog extends Model
{
    protected $table = 'price_log';

    public $timestamps = false;

    protected $fillable = ['item_id', 'old_price', 'new_price', 'by'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}