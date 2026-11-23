# MISSING FEATURES & FIXES — What was incomplete and how it was completed

## Fixed during implementation
1. **Cross-module FK ordering** — moved base `locations` seed into Organization migration so `factory`/`main` exist before dependent FKs.
2. **MySQL/MariaDB portability** — replaced PostgreSQL partial unique indexes with nullable-unique columns (`shifts.open_cashier`, `online_order_boxes.active_box`); replaced `CASE` generated column.
3. **Reserved word `change`** — quoted `paid_cash - \`change\`` in shift close aggregate.
4. **`updated_at` on append-only tables** — disabled Eloquent timestamps on `shift`, `sale`, `stock_moves`, `trip_boxes`, `receipt_lines`, etc.
5. **UUID user FKs** — `push_tokens.user_id` changed to `char(36)`; notifications use `uuidMorphs`.
6. **PSR-4 casing** — `Database/Seeders` dirs capitalised to match namespaces.
7. **Pivot table naming** — explicit `role_permission` in Role/Permission relations.
8. **Decimal JSON assertion** — POS total returned as `"20.00"` (decimal cast); test updated.

## Features added beyond initial plan
- Notifications module (Expo push + tokens + order-status push).
- Sales returns (restock on approve) and purchase returns (stock-out on approve).
- QA spec engine (item_spec_versions + receipt_checks/docs + temp/shelf validation).
- Authorization matrix + concurrency test suites.
- Public customer Shop + Track Blade pages; Accounts overview; Crates management.

## Still missing (tracked in REMAINING_BLOCKERS_AR.md)
- Browser (Dusk) tests, pixel visual parity, data import, Expo re-point, Email/SMS, exports.