<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $fillable = ['no', 'supplier_id', 'amount', 'method', 'ref', 'note', 'by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}