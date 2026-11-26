<?php

namespace App\Modules\Sales\Models;

use App\Models\User;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    public $timestamps = false;

    public const STATUSES = ['open', 'paused', 'closed'];

    protected $fillable = [
        'location_id', 'pos_terminal', 'cashier', 'closed_by', 'opened_at', 'opening_cash',
        'closed_at', 'logged_out_at', 'counted_cash', 'expected_cash', 'close_notes',
        'expenses_total', 'sent_to_manager', 'carryover_to_next_shift', 'notes',
        'status', 'open_cashier',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'logged_out_at' => 'datetime',
            'opening_cash' => 'decimal:2',
            'counted_cash' => 'decimal:2',
            'expected_cash' => 'decimal:2',
            'expenses_total' => 'decimal:2',
            'sent_to_manager' => 'decimal:2',
            'carryover_to_next_shift' => 'decimal:2',
        ];
    }

    public function cashierUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /** Collections recorded on this shift (online-order receiving etc.). */
    public function collections(): HasMany
    {
        return $this->hasMany(ShiftCollection::class);
    }

    /**
     * Cash custody added to this shift by cash collections only.
     * InstaPay/card collections are recorded for reports and do NOT
     * increase custody.
     */
    public function cashCollectionsTotal(): float
    {
        return (float) $this->collections()->where('method', 'cash')->sum('amount');
    }

    public function nonCashCollectionsTotal(): float
    {
        return (float) $this->collections()->whereIn('method', ['instapay', 'card'])->sum('amount');
    }

    public function stockMoves(): HasMany
    {
        return $this->hasMany(StockMove::class, 'shift_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'shift_id');
    }

    /** Shift-specific expenses (with approval workflow). */
    public function shiftExpenses(): HasMany
    {
        return $this->hasMany(ShiftExpense::class);
    }

    /** Cash transfers outgoing from this shift. */
    public function cashTransfers(): HasMany
    {
        return $this->hasMany(CashTransfer::class, 'from_shift_id');
    }

    /** Cash transfers incoming to this shift. */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(CashTransfer::class, 'to_shift_id');
    }

    public function workingSeconds(): int
    {
        $end = $this->closed_at ?? now();

        return max(0, $end->getTimestamp() - ($this->opened_at?->getTimestamp() ?? $end->getTimestamp()));
    }

    public function workingHours(): float
    {
        return round($this->workingSeconds() / 3600, 2);
    }
}