<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receipt extends Model
{
    protected $fillable = ['no', 'po_id', 'supplier_id', 'result', 'flags', 'override_id', 'invoice_no', 'by'];

    protected function casts(): array
    {
        return ['flags' => 'array'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReceiptLine::class);
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'po_id');
    }
}