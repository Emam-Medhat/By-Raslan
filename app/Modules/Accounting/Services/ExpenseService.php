<?php

namespace App\Modules\Accounting\Services;

use App\Models\User;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Audit\Services\AuditService;
use App\Modules\Sales\Models\Shift;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly AuditService $audit)
    {
    }
    public function add(User $user, array $data): Expense
    {
        $amount = (float) $data['amount'];

        if ($amount <= 0) {
            throw new \RuntimeException('اكتب مبلغاً صحيحاً');
        }

        $status = 'ok';
        $limit = $user->permissionLimit('acc.post');
        if ($limit !== null && $amount > $limit) {
            $status = 'pending';
        }

        $shiftId = null;
        if (($data['paid_from'] ?? 'cash') === 'shift') {
            $shift = Shift::where('cashier', $user->id)->where('status', 'open')->lockForUpdate()->first();
            if (! $shift) {
                throw new \RuntimeException('مفيش وردية مفتوحة باسمك');
            }
            if ($shift->location_id !== $data['location_id']) {
                throw new \RuntimeException('مصروف الدرج لازم يكون من فرع الوردية');
            }
            $shiftId = $shift->id;
        }

        $expense = Expense::create([
            'no' => (int) max(5000, (int) Expense::max('no')) + 1,
            'day' => $data['day'] ?? now()->toDateString(),
            'location_id' => $data['location_id'],
            'category_id' => $data['category_id'],
            'amount' => $amount,
            'paid_from' => $data['paid_from'] ?? 'cash',
            'shift_id' => $shiftId,
            'note' => $data['note'] ?? null,
            'photo' => $data['photo'] ?? null,
            'status' => $status,
            'by' => $user->id,
            'at' => now(),
        ]);

        $this->audit->log('expense.add', 'expenses', (string) $expense->id, null, ['amount' => $expense->amount]);

        return $expense;
    }

    public function decide(User $user, Expense $expense, bool $approve, ?string $note): void
    {
        if ($expense->status !== 'pending') {
            throw new \RuntimeException('المصروف ده مش مستني موافقة');
        }

        $limit = $user->permissionLimit('acc.approve');
        if ($approve && $limit !== null && (float) $expense->amount > $limit) {
            throw new \RuntimeException('المبلغ أكبر من حد موافقتك');
        }

        $expense->update([
            'status' => $approve ? 'ok' : 'rejected',
            'decided_by' => $user->id,
            'decided_at' => now(),
            'decision_note' => $note,
        ]);
    }
}