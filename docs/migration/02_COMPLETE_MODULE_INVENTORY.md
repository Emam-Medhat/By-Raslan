# 02 — Complete Module Inventory

Status legend: ✅ implemented + tested · ◐ partial · ○ not started.

| # | Module (Laravel) | Key functions | Status |
|---|---|---|---|
| 1 | Identity & Access | login/lockout, users, roles, permissions, grants (until/max_amount) | ✅ |
| 2 | Organization | locations/branches tree, type scopes | ✅ |
| 3 | Catalog | families, items, barcodes/PLU, prices (loc×channel), price_log | ✅ |
| 4 | Inventory | boxes, append-only stock_moves, balances, adjust/waste/donate, box history | ✅ |
| 5 | Transfers | scan-based send/receive, missing boxes | ✅ |
| 6 | Trips & Loading | crates, trips, load/unload, depart→transfers | ✅ |
| 7 | Sales / POS | shifts, lookup, idempotent sale, discount, close shift | ✅ |
| 8 | Accounting | expense categories, expenses, limits/approval, shift expenses | ✅ |
| 9 | Purchasing | suppliers, PO, receive→lots+stock, supplier payments/balances, overrides | ✅ |
| 10 | Production & Packing | item_routes, prod_orders/steps, pack_box, pack_finish | ✅ |
| 11 | Settings | key/value settings + UI | ✅ |
| 12 | Orders & Delivery | customers, online orders, scan/prepare, deliver, public API | ✅ |
| 13 | Audit | audit_log + viewer (login/expense wired) | ✅ |
| 14 | Reporting | dashboard KPIs, 7-day statistics | ✅ |
| 15 | Notifications | push tokens, Expo push job, order-status push | ✅ |
| 16 | Returns | sales return request/approve→restock | ✅ |

## Explicitly out-of-scope-for-now (present in legacy, NOT yet built)
- Full QA cert/photo document checklist (checks + temp + shelf validation done; cert photos ◐).
- Chart of accounts / treasury / multi-bank (legacy has none; expenses + supplier payments only).
- Global search, CSV/Excel export, printable invoices.
- Email/SMS notifications (only Expo push built).

Sales returns ✅, purchase returns ✅, QA spec versions ✅ (checks/temp/shelf).