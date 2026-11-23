# DATABASE INTEGRITY REPORT

## Schema build
- `php artisan migrate:fresh --seed` → **20 migrations** (17 module + 3 Laravel) run clean on MariaDB 11.4 (MySQL 8 target).
- ~**60 tables**, all with PK/FK, indexes, unique constraints.

## Type & integrity rules
- Money/weights = `decimal(12,2)` / `decimal(12,3)` (no float).
- UUID users = `char(36)`; FK types aligned.
- Append-only ledgers: `stock_moves`, `audit_log`, `price_log` (insert-only; corrections via reversing entries).
- Unique guards: `sales(cashier,idempotency_key)`, `shifts.open_cashier`, `online_order_boxes.active_box`, `sales_returns.no`, etc.

## Referential integrity (tested)
- Transfers/trips/purchasing/production/orders all assert FK writes + cascade behaviour in tests.
- Receive→lot→stock_move chain validated (PurchasingTest).
- Order deliver→box sold→stock_move validated (OrderTest).

## Data migration (Postgres→MySQL)
- **NOT executed** (no prod dump; requires approval). Plan in `docs/migration/10_DATA_MIGRATION_PLAN.md`.
- Reconciliation checks (row counts, Σ sales/stock, FK orphans) specified but pending a source dump.

## Verdict
Schema is sound and tested on a disposable DB. Production data import is the remaining step (blocked on dump + approval).