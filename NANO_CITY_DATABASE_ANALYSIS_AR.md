# NANO CITY DATABASE ANALYSIS — تحليل قاعدة البيانات

## 1. Overview

Nano City uses MySQL with the following key tables for the targeted modules:

## 2. Branches Tables

### `branches`
```sql
CREATE TABLE branches (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    created_by BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(255) NULL,
    phone VARCHAR(255) NULL,
    address VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    business_id BIGINT UNSIGNED NULL,
    owner_id BIGINT UNSIGNED NULL,
    region_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (business_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### `branch_users`
```sql
CREATE TABLE branch_users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role VARCHAR(50) DEFAULT 'manager',
    is_primary_manager BOOLEAN DEFAULT FALSE,
    can_manage_permissions BOOLEAN DEFAULT FALSE,
    permissions JSON NULL,
    assigned_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY (branch_id, user_id),
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### `branch_links`
```sql
CREATE TABLE branch_links (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    from_branch_id BIGINT UNSIGNED NOT NULL,
    to_branch_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) DEFAULT 'transfer',
    is_active BOOLEAN DEFAULT TRUE,
    business_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## 3. Shifts Tables

### `shifts`
```sql
CREATE TABLE shifts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    store_id BIGINT UNSIGNED NULL,
    starting_cash DECIMAL(10,2) DEFAULT 0,
    expected_cash DECIMAL(10,2) NULL,
    ending_cash DECIMAL(10,2) NULL,
    cash_difference DECIMAL(10,2) NULL,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    status ENUM('active','paused','closed') DEFAULT 'active',
    closed_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    expenses_total DECIMAL(12,2) DEFAULT 0,
    sent_to_manager DECIMAL(12,2) DEFAULT 0,
    carryover_to_next_shift DECIMAL(12,2) DEFAULT 0,
    payments_breakdown JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);
```

### `shift_expenses`
```sql
CREATE TABLE shift_expenses (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    shift_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) DEFAULT 0,
    expense_date DATETIME NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    approved_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    receipt_image VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);
```

### `cash_transfers`
```sql
CREATE TABLE cash_transfers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    from_shift_id BIGINT UNSIGNED NULL,
    to_shift_id BIGINT UNSIGNED NULL,
    branch_id BIGINT UNSIGNED NULL,
    from_user_id BIGINT UNSIGNED NULL,
    to_user_id BIGINT UNSIGNED NULL,
    type ENUM('to_manager','to_next_shift','to_safe') DEFAULT 'to_manager',
    amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    approved_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (from_shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    FOREIGN KEY (to_shift_id) REFERENCES shifts(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);
```

### `shift_incomes`
```sql
CREATE TABLE shift_incomes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    shift_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(12,2) DEFAULT 0,
    income_date DATETIME NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'approved',
    approved_by BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (shift_id) REFERENCES shifts(id) ON DELETE CASCADE
);
```

## 4. Accounting Tables

### `accounts`
```sql
CREATE TABLE accounts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('asset','liability','equity','revenue','expense') NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    branch_id BIGINT UNSIGNED NULL,
    opening_balance DECIMAL(15,2) DEFAULT 0,
    opening_balance_date DATE NULL,
    opening_balance_type VARCHAR(20) NULL,
    category VARCHAR(100) NULL,
    description TEXT NULL,
    is_header BOOLEAN DEFAULT FALSE,
    allow_posting BOOLEAN DEFAULT TRUE,
    account_number VARCHAR(50) NULL,
    currency VARCHAR(3) DEFAULT 'EGP',
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (parent_id) REFERENCES accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
);
```

### `journal_entries`
```sql
CREATE TABLE journal_entries (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    description TEXT NULL,
    reference_number VARCHAR(255) NULL,
    branch_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### `journal_entry_lines`
```sql
CREATE TABLE journal_entry_lines (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id BIGINT UNSIGNED NOT NULL,
    account_id BIGINT UNSIGNED NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);
```

## 5. Inventory Tables

### `inventories`
```sql
CREATE TABLE inventories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NULL,
    item_id BIGINT UNSIGNED NULL,
    quantity DECIMAL(12,3) DEFAULT 0,
    unit_cost DECIMAL(12,2) DEFAULT 0,
    inventory_account_id BIGINT UNSIGNED NULL,
    cogs_account_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### `inventory_movements`
```sql
CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    inventory_id BIGINT UNSIGNED NULL,
    branch_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    type ENUM('sale','sale_return','waste','consumption','production_in','production_out','adjustment','transfer_in','transfer_out') NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    total_cost DECIMAL(12,2) DEFAULT 0,
    description TEXT NULL,
    movement_date DATETIME NULL,
    reference_type VARCHAR(100) NULL,
    reference_id BIGINT UNSIGNED NULL,
    entry_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### `stock_counts`
```sql
CREATE TABLE stock_counts (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    branch_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL,
    count_number VARCHAR(50) NOT NULL,
    status ENUM('draft','in_progress','completed','approved') DEFAULT 'draft',
    approved_by BIGINT UNSIGNED NULL,
    approved_at DATETIME NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## 6. Relationships

### Entity Relationship Diagram

```
users (1) ──── (N) branch_users (N) ──── (1) branches
  │                    │
  │                    └── (N) shifts
  │                           │
  │                           ├── (N) shift_expenses
  │                           ├── (N) cash_transfers (from_shift)
  │                           ├── (N) cash_transfers (to_shift)
  │                           └── (N) shift_incomes
  │
  ├── (N) shifts (as cashier)
  ├── (N) shifts (as closed_by)
  ├── (N) accounts (as created_by)
  └── (N) expenses

branches (1) ──── (N) branches (as parent via region)
  │
  ├── (1) ──── (N) shifts
  ├── (1) ──── (N) shift_expenses
  ├── (1) ──── (N) cash_transfers
  ├── (1) ──── (N) accounts
  ├── (1) ──── (N) inventories
  └── (1) ──── (N) stock_counts

accounts (1) ──── (N) journal_entry_lines
journal_entries (1) ──── (N) journal_entry_lines
```

## 7. Key Constraints & Indexes

### Unique Constraints
- `branch_users`: UNIQUE(branch_id, user_id)
- `accounts`: UNIQUE(code)
- `shifts`: No unique constraint (multiple shifts per user allowed, but only one active)

### Indexes
- `shifts`: INDEX(user_id, status) - for finding active shifts
- `shifts`: INDEX(branch_id)
- `shift_expenses`: INDEX(shift_id), INDEX(status)
- `cash_transfers`: INDEX(from_shift_id), INDEX(to_shift_id)
- `accounts`: INDEX(type), INDEX(branch_id)
- `journal_entries`: INDEX(reference_number), INDEX(branch_id)
- `inventory_movements`: INDEX(inventory_id), INDEX(reference_type, reference_id)

## 8. Data Flow

### Shift Creation Flow
1. User opens shift → `shifts` record created (status=active)
2. Attendance record auto-created
3. Notification sent

### Sale During Shift
1. POS creates sale → `sales` record with shift_id
2. AccountingIntegrationService.postSale() → journal entry created
3. Stock deducted → `inventory_movements` created

### Shift Closing Flow
1. Calculate expenses_total from shift_expenses
2. Calculate sent_to_manager from cash_transfers
3. Calculate expected_cash = starting_cash + cash_sales - expenses - transfers
4. User enters ending_cash (actual)
5. Calculate cash_difference = ending_cash - expected_cash
6. Create carryover transfer if ending_cash > 0
7. Update shift status to closed
8. AccountingIntegrationService.postShiftClosing() → journal entry for difference
9. Notification sent

### Stock Count Flow
1. Create stock_count record
2. Add stock_count_items with actual vs system quantities
3. Calculate differences
4. Approve → create inventory_movements for adjustments
5. AccountingIntegrationService.postStockCount() → journal entries for surplus/deficit