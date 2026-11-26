<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One collection recorded against a shift.
 *
 * Rule (manager): cash collections increase the receiving user's custody
 * in that branch; instapay/card collections are recorded for reports
 * only and do NOT increase custody.
 */
class ShiftCollection extends Model
{
    public const METHODS = ['cash', 'instapay', 'card'];

    public const METHOD_LABELS = [
        'cash' => 'كاش',
        'instapay' => 'إنستا باي',
        'card' => 'فيزا / محفظة',
    ];

    protected $fillable = [
        'shift_id', 'user_id', 'location_id', 'order_id', 'sale_id', 'method', 'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function isCash(): bool
    {
        return $this->method === 'cash';
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(OnlineOrder::class, 'order_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
