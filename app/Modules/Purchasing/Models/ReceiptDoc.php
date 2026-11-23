<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptDoc extends Model
{
    public $timestamps = false;

    protected $fillable = ['receipt_id', 'item_id', 'doc_name', 'photo', 'missing', 'created_at'];

    protected function casts(): array
    {
        return ['missing' => 'boolean', 'created_at' => 'datetime'];
    }
}