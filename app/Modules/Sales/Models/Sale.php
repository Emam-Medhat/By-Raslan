<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public const UPDATED_AT = null;

    public const CREATED_AT = 'at';

    protected $fillable = [
        'no', 'location_id', 'shift_id', 'cashier', 'at', 'subtotal', 'discount', 'total',
        'paid_cash', 'paid_card', 'change', 'idempotency_key', 'request_payload', 'discount_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_cash' => 'decimal:2',
            'paid_card' => 'decimal:2',
            'change' => 'decimal:2',
            'request_payload' => 'array',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function cashierUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier');
    }
}