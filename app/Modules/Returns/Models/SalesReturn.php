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

    /** Where the returned tray goes. One destination per return. */
    public const DESTINATIONS = ['stock', 'waste', 'donated'];

    public const DESTINATION_LABELS = [
        'stock' => 'مخزون الفرع (إعادة بيع)',
        'waste' => 'هالك',
        'donated' => 'مخزن تبرعات',
    ];

    /** How the customer was refunded; 'none' = no money back (e.g. exchange). */
    public const REFUND_METHODS = ['cash', 'card', 'none'];

    public const REFUND_METHOD_LABELS = [
        'cash' => 'كاش',
        'card' => 'فيزا / محفظة',
        'none' => 'بدون استرداد',
    ];

    protected $fillable = [
        'no', 'sale_id', 'shift_id', 'location_id', 'destination',
        'refund_method', 'refund_amount', 'subtotal', 'total', 'status',
        'reason', 'by', 'at', 'decided_by', 'decided_at',
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

    /** The shift the refund was charged against (approver's open shift). */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function destinationLabel(): string
    {
        return self::DESTINATION_LABELS[$this->destination] ?? (string) $this->destination;
    }

    public function refundMethodLabel(): string
    {
        return self::REFUND_METHOD_LABELS[$this->refund_method] ?? (string) $this->refund_method;
    }
}