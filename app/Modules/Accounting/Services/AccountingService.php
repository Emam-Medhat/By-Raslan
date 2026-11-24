<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

/**
 * Accounting service: creates journal entries for financial transactions.
 * Mirrors Nano City's AccountingIntegrationService.
 */
class AccountingService
{
    /**
     * Create a journal entry with lines.
     */
    public function createEntry(string $date, string $description, ?string $reference, array $lines, ?string $locationId = null): JournalEntry
    {
        return DB::transaction(function () use ($date, $description, $reference, $lines, $locationId) {
            $entry = JournalEntry::create([
                'date' => $date,
                'description' => $description,
                'reference_number' => $reference,
                'location_id' => $locationId,
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);

                // Update account balance
                $account = Account::find($line['account_id']);
                if ($account) {
                    $account->balance = (float) $account->balance + (float) ($line['debit'] ?? 0) - (float) ($line['credit'] ?? 0);
                    $account->save();
                }
            }

            return $entry;
        });
    }

    /**
     * Post a sale journal entry.
     * Dr. Cash/Bank (paid_cash + paid_card)
     * Cr. Sales Revenue (total)
     */
    public function postSale($sale): void
    {
        $cash = $this->resolveAccount('1101', 'الصندوق', 'asset');
        $revenue = $this->resolveAccount('4100', 'إيرادات المبيعات', 'revenue');

        $this->createEntry(
            $sale->at->toDateString(),
            "بيع فاتورة #{$sale->no}",
            "sale:{$sale->id}",
            [
                ['account_id' => $cash->id, 'debit' => (float) $sale->paid_cash + (float) $sale->paid_card, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => (float) $sale->total],
            ],
            $sale->location_id
        );
    }

    /**
     * Post an expense journal entry.
     * Dr. Expense Account
     * Cr. Cash/Bank
     */
    public function postExpense($expense): void
    {
        $cash = $this->resolveAccount('1101', 'الصندوق', 'asset');
        $expenseAcc = $this->resolveAccount('5200', 'مصروفات التشغيل', 'expense');

        $this->createEntry(
            $expense->day,
            "مصروف: {$expense->category->name}",
            "expense:{$expense->id}",
            [
                ['account_id' => $expenseAcc->id, 'debit' => (float) $expense->amount, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => (float) $expense->amount],
            ],
            $expense->location_id
        );
    }

    /**
     * Post shift closing journal entry for cash difference.
     * If surplus (counted > expected): Dr. Cash / Cr. Over/Short
     * If deficit (counted < expected): Dr. Over/Short / Cr. Cash
     */
    public function postShiftClosing($shift): void
    {
        $diff = (float) $shift->counted_cash - (float) $shift->expected_cash;
        if (abs($diff) < 0.01) {
            return;
        }

        $cash = $this->resolveAccount('1101', 'الصندوق', 'asset');
        $overShort = $this->resolveAccount('6100', 'فروقات الصندوق', 'expense');

        if ($diff > 0) {
            // Surplus
            $this->createEntry(
                now()->toDateString(),
                "فائض صندوق وردية #{$shift->id}",
                "shift_close:{$shift->id}",
                [
                    ['account_id' => $cash->id, 'debit' => $diff, 'credit' => 0],
                    ['account_id' => $overShort->id, 'debit' => 0, 'credit' => $diff],
                ],
                $shift->location_id
            );
        } else {
            // Deficit
            $this->createEntry(
                now()->toDateString(),
                "عجز صندوق وردية #{$shift->id}",
                "shift_close:{$shift->id}",
                [
                    ['account_id' => $overShort->id, 'debit' => abs($diff), 'credit' => 0],
                    ['account_id' => $cash->id, 'debit' => 0, 'credit' => abs($diff)],
                ],
                $shift->location_id
            );
        }
    }

    /**
     * Post shift expense journal entry.
     * Dr. Expense Account
     * Cr. Cash
     */
    public function postShiftExpense($expense): void
    {
        if ($expense->status !== 'approved') {
            return;
        }

        $cash = $this->resolveAccount('1101', 'الصندوق', 'asset');
        $expenseAcc = $this->resolveAccount('5200', 'مصروفات التشغيل', 'expense');

        $this->createEntry(
            $expense->expense_date?->toDateString() ?? now()->toDateString(),
            "مصروف وردية: {$expense->title}",
            "shift_expense:{$expense->id}",
            [
                ['account_id' => $expenseAcc->id, 'debit' => (float) $expense->amount, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => (float) $expense->amount],
            ],
            $expense->location_id
        );
    }

    /**
     * Resolve or create an account by code.
     */
    private function resolveAccount(string $code, string $name, string $type): Account
    {
        $account = Account::where('code', $code)->first();
        if ($account) {
            return $account;
        }

        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'balance' => 0,
            'is_active' => true,
            'allow_posting' => true,
        ]);
    }
}