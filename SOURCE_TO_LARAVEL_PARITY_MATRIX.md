# SOURCE → LARAVEL PARITY MATRIX

Legend: COMPLETE · PARTIAL · MISSING · NOT_TESTED. Source = Next.js `src/app` + `supabase/migrations`.

## Pages / Routes
| Source page | Laravel route | Status |
|---|---|---|
| /login | `login` | COMPLETE |
| /dashboard | `dashboard` | COMPLETE |
| /statistics | `statistics.index` | COMPLETE |
| /pos | `pos.index` | COMPLETE |
| /sales | `sales.index` | COMPLETE |
| /orders, /orders/[id] | `orders.index`, `orders.show` | COMPLETE |
| /order (public shop), /t/[token] | `shop.index`, `shop.track` | COMPLETE |
| /shifts (shift mgmt) | `shifts.index` | COMPLETE |
| /returns (new) | `returns.*` | COMPLETE |
| /stock, /stock/dispose | `stock.index`, `stock.dispose` | COMPLETE |
| /boxes | `boxes.index` | COMPLETE |
| /transfers, /transfers/new, /[id] | `transfers.index/create/show` | COMPLETE |
| /trips, /trips/[id] | `trips.index/create/show` | COMPLETE |
| /purchasing, receive, receipts | `purchasing.*` | COMPLETE |
| /purchasing/specs, [id] | `purchasing.specs.index` | COMPLETE |
| /purchasing/suppliers | `purchasing.suppliers.index` | COMPLETE |
| /overrides | `overrides.index` | COMPLETE |
| /production, /production/routes | `production.*` | COMPLETE |
| /pack | `pack.index` | COMPLETE |
| /accounts, /accounts/expenses | `accounts.index`, `expenses.index` | COMPLETE |
| /items | `items.index` (create; edit ◐) | PARTIAL |
| /locations, /branches/[id] | `locations.index` | COMPLETE |
| /users | `users.index/create` (edit ◐) | PARTIAL |
| /settings | `settings.index` | COMPLETE |
| /crates | `crates.index` | COMPLETE |
| notifications | `notifications.index` | COMPLETE |
| audit | `audit.index` | COMPLETE |

## Server Actions / RPC → Services (see MIGRATION_FUNCTION_PARITY.md)
All 32 mapped functions COMPLETE except `can_override` (manual ◐) and `shift_management_report` (◐).

## Database objects
48 tables / 9 views / ~60 functions / 15 triggers / 100+ policies → 60 MySQL tables / services + policies. COMPLETE for core; views recomputed in services.

## Mobile
Supabase RPC + OTP → Laravel public API (`/api/branches|menu|orders|track|push-token`). COMPLETE on server; Expo client re-point = MISSING (client change).