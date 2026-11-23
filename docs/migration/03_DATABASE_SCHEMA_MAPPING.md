# 03 — Database Schema Mapping

PostgreSQL (Supabase) → MySQL 8 (Laravel). Target uses **MySQL 8** per explicit decision; Laravel migrations are the schema source of truth.

## Type mapping
| PostgreSQL | MySQL 8 / Laravel |
|---|---|
| `uuid` | `char(36)` (Laravel `HasUuids`) |
| `bigint generated always as identity` | `bigIncrements` / `id()` |
| `text` (PK code) | `string` PK |
| `jsonb` | `json` |
| `numeric(12,2)` | `decimal(12,2)` |
| `numeric` (qty) | `decimal(12,3)` |
| `boolean` | `boolean` (tinyint) |
| `timestamptz` | `timestamp` (UTC) |
| enum via CHECK | `string` + validation / `CHECK` |
| partial unique index | nullable generated/unique column (e.g. `open_cashier`, `active_box`) |

## Table mapping (legacy → Laravel)
- auth.users + profiles → `users` (char36 id, phone, password, is_owner, active, home_location_id)
- grants → `permission_user` (pivot with until, max_amount, granted_by)
- roles/permissions → `roles`, `permissions`, `role_user`, `role_permission`
- locations → `locations` (+ base tree seeded in migration)
- families/items/item_barcodes → `families`, `items`, `item_barcodes`
- prices/price_log → `prices`, `price_log`
- boxes/stock_moves → `boxes`, `stock_moves` (append-only)
- transfers/transfer_boxes → `transfers`, `transfer_boxes`
- crates/trips/trip_crates/trip_boxes → `crates`, `trips`, `trip_crates`, `trip_boxes`
- shifts/sales/sale_lines → `shifts`, `sales`, `sale_lines` (+ idempotency_key, request_payload, discount_by)
- expense_categories/expenses → `expense_categories`, `expenses`
- suppliers/purchase_orders/po_lines/receipts/receipt_lines/lots/supplier_payments/override_requests → same names
- item_routes/prod_orders/prod_steps → same names
- customers/online_orders/online_order_lines/online_order_boxes → same names
- settings → `settings` (key/value)
- audit → `audit_log`
- push_tokens → `push_tokens`; notifications → `notifications` (uuid morph)
- sales returns → `sales_returns`, `sales_return_lines`

## Migrations (15 module migrations + 3 Laravel defaults)
Order: users → identity → locations → catalog → inventory → transfers → trips → sales → accounting → purchasing → production → settings → orders → audit → notifications → returns.

## Notes
- Financial/weight values are `decimal` (never float).
- Append-only ledgers (`stock_moves`, `audit_log`, `price_log`) enforced at service layer.
- RLS is replaced by Laravel Policies/Gates + `perm` middleware (see `14_SECURITY_AUDIT.md`).