<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['receipt_id', 'line', 'item_id', 'weighed', 'rejected', 'accepted', 'price', 'temp', 'expires', 'spec_version', 'lot_code', 'note'];

    protected function casts(): array
    {
        return [
            'weighed' => 'decimal:3',
            'rejected' => 'decimal:3',
            'accepted' => 'decimal:3',
            'price' => 'decimal:2',
            'expires' => 'date',
        ];
    }
}