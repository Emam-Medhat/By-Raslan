<?php

namespace App\Modules\Returns\Models;

use App\Modules\Purchasing\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    public $timestamps = false;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = ['no', 'supplier_id', 'location_id', 'total', 'status', 'reason', 'by', 'at', 'decided_by', 'decided_at'];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'at' => 'datetime', 'decided_at' => 'datetime'];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReturnLine::class, 'return_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}