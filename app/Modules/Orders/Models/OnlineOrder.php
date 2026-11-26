<?php

namespace App\Modules\Orders\Models;

use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Modules\Organization\Models\Location;

class OnlineOrder extends Model
{
    public const STATUSES = ['new', 'accepted', 'preparing', 'ready', 'out', 'delivered', 'cancelled'];

    public const COLLECTION_METHODS = ['cash', 'instapay', 'card'];

    protected $fillable = [
        'no', 'token', 'customer_id', 'address_id', 'location_id', 'channel', 'from_app', 'status', 'address', 'note',
        'pay_method', 'est_total', 'subtotal', 'delivery_fee', 'total', 'paid', 'driver', 'driver_id',
        'shift_id', 'collection_method', 'collected_at',
        'cancel_reason', 'handled_by', 'accepted_at', 'ready_at', 'out_at', 'delivered_at',
        'driver_id', 'collection_method', 'collected_amount', 'collected_by', 'collected_at',
        'sale_id', 'reservation_id',
    ];

    protected function casts(): array
    {
        return [
            'est_total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'paid' => 'boolean',
            'from_app' => 'boolean',
            'accepted_at' => 'datetime',
            'ready_at' => 'datetime',
            'out_at' => 'datetime',
            'delivered_at' => 'datetime',
            'collected_amount' => 'decimal:2',
            'collected_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Free-text driver name kept for old orders; new orders use driver_id. */
    public function driverName(): string
    {
        return $this->driverRecord?->name ?? (string) $this->driver;
    }

    public function driverRecord(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function collections(): HasMany
    {
        return $this->hasMany(ShiftCollection::class, 'order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OnlineOrderLine::class, 'order_id');
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(OnlineOrderBox::class, 'order_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}
