<?php

namespace App\Modules\Purchasing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    public const STATUSES = ['open', 'partial', 'done', 'cancelled'];

    protected $fillable = ['no', 'supplier_id', 'status', 'expected', 'note', 'created_by'];

    protected function casts(): array
    {
        return ['expected' => 'date'];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PoLine::class, 'po_id');
    }
}