<?php

namespace App\Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineOrder extends Model
{
    public const STATUSES = ['new', 'accepted', 'preparing', 'ready', 'out', 'delivered', 'cancelled'];

    protected $fillable = [
        'no', 'token', 'customer_id', 'address_id', 'location_id', 'channel', 'from_app', 'status', 'address', 'note',
        'pay_method', 'est_total', 'subtotal', 'delivery_fee', 'total', 'paid', 'driver',
        'cancel_reason', 'handled_by', 'accepted_at', 'ready_at', 'out_at', 'delivered_at',
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
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OnlineOrderLine::class, 'order_id');
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(OnlineOrderBox::class, 'order_id');
    }
}