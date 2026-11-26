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

class ShiftService
{
    public function create(array $data): Shift
    {
        $existing = Shift::where('cashier', $data['cashier'])->where('status', 'open')->first();
        if ($existing) {
            throw ValidationException::withMessages(['cashier' => 'هذا الكاشير لديه وردية مفتوحة بالفعل']);
        }

        $location = Location::where('id', $data['location_id'])
            ->where('active', true)
            ->whereIn('type', ['dark', 'shop', 'factory', 'section'])
            ->first();
        if (!$location) {
            throw ValidationException::withMessages(['location_id' => 'الموقع غير متاح']);
        }

        $previous = Shift::where('cashier', $data['cashier'])
            ->where('status', 'closed')
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->first();
        $carry = round((float) ($previous?->carryover_to_next_shift ?? $previous?->expected_cash ?? 0), 2);
        $opening = $previous
            ? $carry
            : round((float) ($data['opening_cash'] ?? 0), 2);

        return Shift::create([
            'location_id' => $data['location_id'],
            'cashier' => $data['cashier'],
            'open_cashier' => $data['cashier'],
            'opened_at' => $data['opened_at'] ?? now(),
            'opening_cash' => $opening,
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

    public function openShift(string $location, float $cash, string $userId): Shift
    {
        $existing = Shift::where('cashier', $userId)->where('status', 'open')->first();
        if ($existing) return $existing;
        return $this->create(['location_id' => $location, 'cashier' => $userId, 'opening_cash' => $cash]);
    }

    public function closeShift(int $shiftId, ?float $countedCash, string $userId, ?string $notes = null): float
    {
        return DB::transaction(function () use ($shiftId, $countedCash, $userId, $notes) {
            $shift = Shift::where('id', $shiftId)->where('status', 'open')->lockForUpdate()->first();
            if (!$shift) throw new \RuntimeException('الوردية دي مش مفتوحة');

            if ($shift->cashier !== $userId) {
                $actor = User::find($userId);
                if (!$actor || !$actor->hasPermission('pos.shift')) {
                    throw new \RuntimeException('مش مسموح بإغلاق وردية غيرك');
                }
            }

            $totals = $this->shiftTotals($shift);
            $expected = $totals['expected_cash'];
            $carryover = round($expected, 2); // cumulative custody: not reset

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
                        'notes' => 'عهدة تراكمية مرحّلة للوردية التالية',
                    ]
                );
            }

            $shift->update([
                'status' => 'closed',
                'open_cashier' => null,
                'closed_by' => $userId,
                'closed_at' => now(),
                'logged_out_at' => now(),
                'counted_cash' => $countedCash !== null ? round($countedCash, 2) : null,
                'expected_cash' => $expected,
                'expenses_total' => $totals['shift_expenses_total'],
                'sent_to_manager' => $totals['transfers_out'],
                'carryover_to_next_shift' => $carryover,
                'close_notes' => $notes,
            ]);

            return 0.0;
        });
    }

    public function calculateExpectedCash(Shift $shift): float
    {
        return $this->shiftTotals($shift)['expected_cash'];
    }

    public function shiftTotals(Shift $shift): array
    {
        $salesQ = Sale::where('shift_id', $shift->id);
        $cashSales = (float) (clone $salesQ)->sum(DB::raw('paid_cash - `change`'));
        $electronicSales = (float) (clone $salesQ)->sum('paid_card');
        $totalSales = (float) (clone $salesQ)->sum('total');
        $discountTotal = (float) (clone $salesQ)->sum('discount');
        $deliveryTotal = (float) (clone $salesQ)->sum('delivery_fee');
        $ordersCount = (int) (clone $salesQ)->count();
        $returnsCount = (int) DB::table('sales_returns')->whereIn('sale_id', (clone $salesQ)->select('id'))->where('status', 'approved')->count();
        $returnsTotal = (float) DB::table('sales_returns')->whereIn('sale_id', (clone $salesQ)->select('id'))->where('status', 'approved')->sum('total');
        $netSales = round($totalSales - $returnsTotal, 2);

        $shiftExpenses = (float) ShiftExpense::where('shift_id', $shift->id)->whereIn('status', ['approved', 'pending'])->sum('amount');
        $transfersOut = (float) CashTransfer::where('from_shift_id', $shift->id)->whereIn('type', ['to_manager', 'to_safe'])->whereIn('status', ['approved', 'pending'])->sum('amount');
        $transfersIn = (float) CashTransfer::where('to_shift_id', $shift->id)->whereIn('status', ['approved', 'pending'])->sum('amount');
        $expected = round((float) $shift->opening_cash + $cashSales - $shiftExpenses - $transfersOut + $transfersIn, 2);

        $byMethod = [
            'cash' => round((float) Sale::where('shift_id',$shift->id)->where('pay_method','cash')->sum('total'), 2),
            'card' => round((float) Sale::where('shift_id',$shift->id)->where('pay_method','card')->sum('total'), 2),
            'instapay' => round((float) Sale::where('shift_id',$shift->id)->where('pay_method','instapay')->sum('total'), 2),
            'wallet' => round((float) Sale::where('shift_id',$shift->id)->where('pay_method','wallet')->sum('total'), 2),
            'mixed' => round((float) Sale::where('shift_id',$shift->id)->where('pay_method','mixed')->sum('total'), 2),
        ];

        return compact('cashSales','electronicSales','totalSales','discountTotal','deliveryTotal','ordersCount','returnsCount','returnsTotal','netSales','expected') + [
            'shift_expenses_total' => $shiftExpenses,
            'transfers_out' => $transfersOut,
            'by_method' => $byMethod,
            'expected_cash' => $expected,
        ];
    }

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

    public function getPaginated(array $filters = [], int $perPage = 20)
    {
        $query = Shift::with(['cashierUser', 'location', 'closedBy'])->orderByDesc('id');
        if (!empty($filters['search'])) {
            $keyword = trim($filters['search']);
            $query->where(function ($q) use ($keyword) {
                if (is_numeric($keyword)) $q->orWhere('id', $keyword);
                $q->orWhereHas('cashierUser', fn ($u) => $u->where('name', 'like', "%{$keyword}%"));
                $q->orWhereHas('location', fn ($l) => $l->where('name', 'like', "%{$keyword}%"));
            });
        }
        if (!empty($filters['location_id'])) $query->where('location_id', $filters['location_id']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['cashier'])) $query->where('cashier', $filters['cashier']);
        if (!empty($filters['date_from'])) $query->whereDate('opened_at', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->whereDate('opened_at', '<=', $filters['date_to']);
        return $query->paginate($perPage)->appends($filters);
    }

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
