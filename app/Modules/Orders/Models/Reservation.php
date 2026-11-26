<?php

namespace App\Modules\Orders\Models;

use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    public const STATUSES = ['pending', 'converted', 'cancelled'];

    protected $fillable = [
        'customer_id', 'location_id', 'reservation_date', 'status',
        'delivery_fee', 'pay_method', 'note', 'created_by',
        'converted_order_id', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'delivery_fee' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(OnlineOrder::class, 'converted_order_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(ReservationLine::class, 'reservation_id');
    }
}
