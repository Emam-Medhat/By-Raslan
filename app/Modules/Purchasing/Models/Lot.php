<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['code', 'item_id', 'receipt_id', 'supplier_id', 'qty', 'cost', 'expires', 'location_id'];

    protected function casts(): array
    {
        return ['qty' => 'decimal:3', 'cost' => 'decimal:2', 'expires' => 'date'];
    }
}