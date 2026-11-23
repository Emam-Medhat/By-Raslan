<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    public $timestamps = false;

    public const STATUSES = ['open', 'closed'];

    protected $fillable = [
        'location_id', 'cashier', 'opened_at', 'opening_cash',
        'closed_at', 'counted_cash', 'expected_cash', 'status', 'open_cashier',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
        ];
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}