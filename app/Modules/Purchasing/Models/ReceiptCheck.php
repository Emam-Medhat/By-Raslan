<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptCheck extends Model
{
    public $timestamps = false;

    protected $fillable = ['receipt_id', 'item_id', 'check_text', 'ok', 'created_at'];

    protected function casts(): array
    {
        return ['ok' => 'boolean', 'created_at' => 'datetime'];
    }
}