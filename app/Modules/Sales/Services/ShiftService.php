<?php

namespace App\Modules\Sales\Services;

use App\Models\User;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftCollection;
use App\Modules\Sales\Models\ShiftExpense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enhanced ShiftService: full shift lifecycle management.
 * Mirrors Nano City's ShiftService with create, close, expenses, transfers, carryover.
 */
class ShiftService
{
    /**
     * Create a new shift (open).
     */
    public function create(array $data): Shift
    {
        // Ensure no other open shift for this cashier
        $existing = Shift::where('cashier', $data['cashier'])
            ->where('status', 'open')
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'cashier' => 'هذا الكاشير لديه وردية مفتوحة بالفعل',
            ]);
        }

        // Validate location is sellable
        $location = Location::where('id', $data['location_id'])
            ->where('active', true)
            ->whereIn('type', ['dark', 'shop'])
            ->first();

        if (!$location) {
            throw ValidationException::withMessages([
                'location_id' => 'الفرع غير متاح للبيع',
            ]);
        }

        $shift = Shift::create([
            'location_id' => $data['location_id'],
            'cashier' => $data['cashier'],
            'open_cashier' => $data['cashier'],
            'opened_at' => $data['opened_at'] ?? now(),
            'opening_cash' => round((float) $data['opening_cash'], 2),
            'status' => 'open',
        ]);

        // If the opening cash equals the custody carried over from the
        // previous shift in this branch, link the carryover transfer to
        // this shift so it is consumed exactly once.
        $this->consumeCarryover($shift);

        return $shift;
    }

    /**
     * Custody carried into a new shift in this branch: the carryover of
     * the last closed shift in the branch, provided no shift has been
     * opened in the branch since that shift closed (otherwise the
     * custody was already picked up by the newer shift).
     */
    public function suggestedOpeningCash(string $locationId, ?int $excludeShiftId = null): float
    {
        $last = Shift::where('location_id', $locationId)
            ->where('status', 'closed')
            ->whereNotNull('closed_at')
            ->orderByDesc('closed_at')
            ->first();

        if (! $last || (float) $last->carryover_to_next_shift <= 0) {
            return 0.0;
        }

        $openedAfter = Shift::where('location_id', $locationId)
            ->when($excludeShiftId, fn ($q) => $q->where('id', '!=', $excludeShiftId))
            ->where('opened_at', '>', $last->closed_at)
            ->exists();

        return $openedAfter ? 0.0 : round((float) $last->carryover_to_next_shift, 2);
    }

    /**
     * Link the branch's unconsumed carryover transfer to the new shift,
     * but only when the shift actually opened with exactly that amount.
     */
    public function consumeCarryover(Shift $shift): void
    {
        $suggested = $this->suggestedOpeningCash($shift->location_id, $shift->id);

        if ($suggested <= 0 || abs((float) $shift->opening_cash - $suggested) > 0.01) {
            return;
        }

        CashTransfer::where('location_id', $shift->location_id)
            ->where('type', 'to_next_shift')
            ->where('status', 'approved')
            ->whereNull('to_shift_id')
            ->where('amount', $suggested)
            ->orderByDesc('id')
            ->first()
            ?->update(['to_shift_id' => $shift->id, 'to_user_id' => $shift->cashier]);
    }

    /**
     * Open a shift (alias for create with auto-cashier).
     */
    public function openShift(string $location, float $cash, string $userId): Shift
    {
        return $this->create([
            'location_id' => $location,
            'cashier' => $userId,
            'opening_cash' => $cash,
        ]);
    }

    /**
     * Close a shift with full calculation.
     */
    public function closeShift(int $shiftId, float $countedCash, string $userId, ?string $notes = null): float
    {
        return DB::transaction(function () use ($shiftId, $countedCash, $userId, $notes) {
            $shift = Shift::where('id', $shiftId)->where('status', 'open')->lockForUpdate()->first();

            if (!$shift) {
                throw new \RuntimeException('الوردية دي مش مفتوحة');
            }

            if ($shift->cashier !== $userId) {
                $actor = User::find($userId);
                if (!$actor || !$actor->hasPermission('pos.shift')) {
                    throw new \RuntimeException('مش مسموح بإغلاق وردية غيرك');
                }
            }

            // One expected-cash formula for every close path (see
            // calculateExpectedCash): opening + POS cash + online cash
            // collections - cash return refunds - expenses - transfers.
            $expected = $this->calculateExpectedCash($shift);
            $expensesTotal = $this->expensesTotal($shift->id);
            $transfersToManager = $this->transfersToManagerTotal($shift->id);

            // Carryover = actual ending cash (what remains in drawer)
            $carryover = $countedCash;

            // Create carryover transfer if > 0
            if ($carryover > 0) {
                CashTransfer::updateOrCreate(
                    ['from_shift_id' => $shiftId, 'type' => 'to_next_shift'],
                    [
                        'to_shift_id' => null,
                        'location_id' => $shift->location_id,
                        'from_user_id' => $shift->cashier,
                        'to_user_id' => null,
                        'amount' => $carryover,
                        'status' => 'approved',
                        'approved_by' => $userId,
                        'notes' => 'مبلغ مرحل للوردية التالية',
                    ]
                );
            }

            // Update shift
            $shift->update([
                'status' => 'closed',
                'open_cashier' => null,
                'closed_by' => $userId,
                'closed_at' => now(),
                'logged_out_at' => now(),
                'counted_cash' => $countedCash,
                'expected_cash' => $expected,
                'expenses_total' => $expensesTotal,
                'sent_to_manager' => $transfersToManager,
                'carryover_to_next_shift' => $carryover,
                'close_notes' => $notes,
            ]);

            return round($countedCash - $expected, 2);
        });
    }

    /**
     * Calculate expected cash for a shift — the single formula every close
     * path uses, so the cashier button and the shifts screen can never
     * disagree. The system calculates the cash from the orders; it never
     * asks the user how much is in the drawer (that is only the counted
     * cash entered at close time, compared against this number).
     *
     * expected = opening cash
     *   + POS cash sales (paid_cash - change)
     *   + cash collected on online orders (ShiftCollection method=cash;
     *     instapay/card collections are report-only and do NOT count)
     *   - cash refunds on returns approved against this shift
     *   - expenses (shift expenses + general expenses charged to the shift)
     *   - transfers to manager / safe
     *
     * Custody is cumulative across days: the counted cash becomes the
     * carryover (see closeShift), and the next shift in the branch opens
     * with it (see suggestedOpeningCash / consumeCarryover).
     */
    public function calculateExpectedCash(Shift $shift): float
    {
        $cashSales = (float) Sale::where('shift_id', $shift->id)
            ->sum(DB::raw('paid_cash - `change`'));

        $onlineCash = (float) ShiftCollection::where('shift_id', $shift->id)
            ->where('method', 'cash')->sum('amount');

        $cashRefunds = $this->cashRefundsTotal($shift->id);

        return round(
            (float) $shift->opening_cash + $cashSales + $onlineCash - $cashRefunds
                - $this->expensesTotal($shift->id) - $this->transfersToManagerTotal($shift->id),
            2
        );
    }

    /** Cash refunded on returns approved against this shift. */
    public function cashRefundsTotal(int $shiftId): float
    {
        return (float) \App\Modules\Returns\Models\SalesReturn::where('shift_id', $shiftId)
            ->where('status', 'approved')
            ->where('refund_method', 'cash')
            ->sum('refund_amount');
    }

    /** All expenses charged to the shift (both expense tables). */
    public function expensesTotal(int $shiftId): float
    {
        $shiftExpenses = (float) ShiftExpense::where('shift_id', $shiftId)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount');

        $general = (float) DB::table('expenses')
            ->where('shift_id', $shiftId)
            ->where('status', '!=', 'rejected')
            ->sum('amount');

        return round($shiftExpenses + $general, 2);
    }

    /** Transfers from this shift to the manager or the safe. */
    public function transfersToManagerTotal(int $shiftId): float
    {
        return (float) CashTransfer::where('from_shift_id', $shiftId)
            ->whereIn('type', ['to_manager', 'to_safe'])
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount');
    }

    /**
     * Record a shift expense.
     */
    public function addExpense(Shift $shift, array $data): ShiftExpense
    {
        return ShiftExpense::create([
            'shift_id' => $shift->id,
            'user_id' => auth()->id(),
            'location_id' => $shift->location_id,
            'title' => $data['title'],
            'amount' => round((float) $data['amount'], 2),
            'expense_date' => $data['expense_date'] ?? now(),
            'status' => $data['status'] ?? 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Record a cash transfer.
     */
    public function addTransfer(Shift $shift, array $data): CashTransfer
    {
        return CashTransfer::create([
            'from_shift_id' => $shift->id,
            'to_shift_id' => $data['to_shift_id'] ?? null,
            'location_id' => $shift->location_id,
            'from_user_id' => $shift->cashier,
            'to_user_id' => $data['to_user_id'] ?? null,
            'type' => $data['type'],
            'amount' => round((float) $data['amount'], 2),
            'status' => $data['status'] ?? 'approved',
            'approved_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get paginated shifts with filters.
     */
    public function getPaginated(array $filters = [], int $perPage = 20)
    {
        $query = Shift::with(['cashierUser', 'location', 'closedBy'])->orderByDesc('id');

        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function ($q) use ($keyword) {
                if (is_numeric($keyword)) {
                    $q->orWhere('id', $keyword);
                }
                $q->orWhereHas('cashierUser', fn ($u) => $u->where('name', 'like', "%{$keyword}%"));
                $q->orWhereHas('location', fn ($l) => $l->where('name', 'like', "%{$keyword}%"));
            });
        }

        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['cashier'])) {
            $query->where('cashier', $filters['cashier']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('opened_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('opened_at', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage)->appends($filters);
    }

    /**
     * Get shift statistics.
     */
    public function getStats(): array
    {
        return [
            'total' => Shift::count(),
            'open' => Shift::where('status', 'open')->count(),
            'closed' => Shift::where('status', 'closed')->count(),
            'paused' => Shift::where('status', 'paused')->count(),
            'total_sales' => (float) Sale::sum('total'),
            'total_expenses' => (float) ShiftExpense::whereIn('status', ['approved', 'pending'])->sum('amount'),
        ];
    }
}