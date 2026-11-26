<?php

namespace App\Modules\Returns\Models;

use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    public $timestamps = false;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'no', 'sale_id', 'location_id', 'subtotal', 'total', 'status', 'destination',
        'destination_location_id', 'reason', 'by', 'at', 'decided_by', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2', 'total' => 'decimal:2',
            'refund_amount' => 'decimal:2', 'at' => 'datetime', 'decided_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesReturnLine::class, 'return_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }
}
