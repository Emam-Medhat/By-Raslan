# ACCOUNTING & INVENTORY IMPLEMENTATION — تنفيذ الحسابات والمخزون والجرد

## Accounting Implementation

### 1. New Tables

#### `accounts`
```sql
CREATE TABLE accounts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('asset','liability','equity','revenue','expense') NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    location_id VARCHAR(64) NULL,
    opening_balance DECIMAL(15,2) DEFAULT 0,
    allow_posting BOOLEAN DEFAULT TRUE,
    created_by CHAR(36) NULL,
    timestamps,
    FOREIGN KEY (parent_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE SET NULL
);
```

#### `journal_entries`
```sql
CREATE TABLE journal_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    description TEXT NULL,
    reference_number VARCHAR(255) NULL,
    location_id VARCHAR(64) NULL,
    timestamps
);
```

#### `journal_entry_lines`
```sql
CREATE TABLE journal_entry_lines (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    timestamps,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);
```

### 2. AccountingService

```php
class AccountingService
{
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

    public function postSale(Sale $sale): void
    {
        // Dr. Cash/Bank (paid_cash + paid_card)
        // Cr. Sales Revenue (total)
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

    public function postExpense(Expense $expense): void
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

    public function postShiftClosing(Shift $shift): void
    {
        if (abs((float) $shift->counted_cash - (float) $shift->expected_cash) < 0.01) {
            return;
        }

        $diff = (float) $shift->counted_cash - (float) $shift->expected_cash;
        $cash = $this->resolveAccount('1101', 'الصندوق', 'asset');
        $overShort = $this->resolveAccount('6100', 'فروقات الصندوق', 'expense');

        if ($diff > 0) {
            // Surplus: Dr. Cash / Cr. Over/Short
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
            // Deficit: Dr. Over/Short / Cr. Cash
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
}
```

## Inventory Stocktaking Implementation

### 1. New Tables

#### `stock_counts`
```sql
CREATE TABLE stock_counts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    location_id VARCHAR(64) NOT NULL,
    user_id CHAR(36) NOT NULL,
    count_number VARCHAR(50) NOT NULL,
    status ENUM('draft','in_progress','completed','approved') DEFAULT 'draft',
    approved_by CHAR(36) NULL,
    approved_at TIMESTAMP NULL,
    notes TEXT NULL,
    timestamps,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);
```

#### `stock_count_items`
```sql
CREATE TABLE stock_count_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    stock_count_id BIGINT UNSIGNED NOT NULL,
    item_id BIGINT UNSIGNED NOT NULL,
    system_quantity DECIMAL(12,3) DEFAULT 0,
    actual_quantity DECIMAL(12,3) DEFAULT 0,
    difference DECIMAL(12,3) DEFAULT 0,
    unit_cost DECIMAL(12,2) DEFAULT 0,
    total_cost DECIMAL(12,2) DEFAULT 0,
    notes TEXT NULL,
    timestamps,
    FOREIGN KEY (stock_count_id) REFERENCES stock_counts(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);
```

### 2. StockCountService

```php
class StockCountService
{
    public function create(string $locationId, string $userId): StockCount
    {
        return StockCount::create([
            'location_id' => $locationId,
            'user_id' => $userId,
            'count_number' => 'SC-' . date('Ymd') . '-' . str_pad((string) (StockCount::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);
    }

    public function addItem(StockCount $count, int $itemId, float $actualQty): StockCountItem
    {
        $systemQty = (float) StockMove::where('item_id', $itemId)->where('location_id', $count->location_id)->sum('qty');
        $diff = $actualQty - $systemQty;

        return StockCountItem::create([
            'stock_count_id' => $count->id,
            'item_id' => $itemId,
            'system_quantity' => $systemQty,
            'actual_quantity' => $actualQty,
            'difference' => $diff,
        ]);
    }

    public function approve(StockCount $count, string $userId): void
    {
        DB::transaction(function () use ($count, $userId) {
            foreach ($count->items as $item) {
                if ($item->difference != 0) {
                    StockMove::create([
                        'item_id' => $item->item_id,
                        'location_id' => $count->location_id,
                        'qty' => $item->difference,
                        'kind' => 'count',
                        'reason' => "تسوية جرد #{$count->count_number}",
                        'by' => $userId,
                    ]);
                }
            }

            $count->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        });
    }
}
```