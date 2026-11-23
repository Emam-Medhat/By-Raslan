# DATABASE RELATIONSHIPS AUDIT

## Build
- 20 migrations (17 module + 3 Laravel) run clean on MariaDB 11.4 (MySQL 8 target). ~60 tables.

## Key relationships verified
| Relationship | Type | Verified by |
|---|---|---|
| users ↔ roles ↔ permissions | many-to-many + grants | UserManagementTest |
| items → families / barcodes / prices | belongsTo / hasMany | CatalogTest |
| boxes → stock_moves | hasMany | InventoryTest |
| transfers → transfer_boxes → boxes | hasMany | TransferTest |
| trips → trip_crates / trip_boxes | hasMany | TripTest |
| shifts → sales → sale_lines | hasMany | PosTest |
| purchase_orders → po_lines / receipts | hasMany | PurchasingTest |
| receipts → receipt_lines / lots | hasMany | PurchasingTest |
| online_orders → lines / boxes | hasMany | OrderTest |
| sales_returns / purchase_returns → lines | hasMany | ReturnTest |

## Integrity rules
- FK types aligned (users char36, boxes bigint, locations varchar64).
- Unique guards: `sales(cashier,idempotency_key)`, `shifts.open_cashier`, `online_order_boxes.active_box`, `*.no`.
- Money = `decimal`; quantities = `decimal(12,3)`.
- Append-only ledgers enforced at service layer (insert-only).

## Issues found & fixed
- FK ordering (locations before dependents) — fixed by seeding base tree in migration.
- `push_tokens.user_id` char36; notifications `uuidMorphs` — fixed.
- `change` reserved word quoting — fixed.

## Not done
- Postgres→MySQL data import + reconciliation (needs prod dump). See REMAINING.