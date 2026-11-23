# 01 — Source System Discovery (Next.js → Laravel)

## Source project
- Path: `/mnt/check/By Raslan laravel/Byraslan` (read-only mount).
- Stack: Next.js 15.5, React 19, TypeScript, Supabase (PostgreSQL + Auth + Storage + pg_net), Tailwind.
- Structure: `src/app/**` (pages + server actions), `src/components/**`, `src/lib/**`, `mobile/` (Expo app), `supabase/migrations/*.sql`.

## Verified inventory (audited, not assumed)
- **28 migration files** (`0001`–`0029`, no `0011`).
- **48 tables**, **9 views**, **~60 functions/RPC**, **15 triggers**, **100+ RLS policies**.
- **113 source files** in `src/`; **15 server-action files** (`actions.ts` / `login-action.ts`) exposing ~54 actions.
- **42 web pages** under `src/app`.
- **Mobile**: Expo app `mobile/` with screens index/cart/orders/account/address/login/order.

## Key architectural facts extracted
- Auth: staff password (internal email `{phone}@staff.byraslan.app`) + customer phone OTP via Supabase Auth; `handle_new_user` trigger.
- Authorization: `has_perm()` / `perm_limit()` / `secure_grant()` + RLS per table; 9 role templates in `src/lib/roles.ts`.
- Money & stock: append-only `stock_moves` ledger; `boxes` serialised; prices resolved server-side from `prices`.
- POS: `pos_open_shift`, `pos_lookup`, `pos_sale` (idempotent via `idempotency_key`), `pos_close_shift`; `shift_management_report` (0029).
- Transfers/trips: scan-based box transfers; `trip_depart` creates one transfer per destination.
- Purchasing: `receive_po` (lots + stock + QA checks), supplier balances view.
- Production: `item_routes` versions, `prod_orders`/`prod_steps`, `pack_box`, `prod_pack_finish`.
- Orders: `place_order` (anon), `order_scan`, `order_deliver`, push via `pg_net` → Expo.
- Notifications: Expo push tokens via `push_tokens`; order status push trigger.
- Storage: private `docs` bucket, signed URLs.

## Parity principle
The Laravel rebuild treats this document + `supabase/migrations` as source of truth. See `06_SERVER_ACTIONS_AND_RPC_MAPPING.md` and `MIGRATION_FUNCTION_PARITY.md` for function-level status.