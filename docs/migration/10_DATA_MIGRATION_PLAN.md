# 10 — Data Migration Plan (PostgreSQL → MySQL)

## Principle
Do NOT touch production. Migrate into a staging MySQL, validate, then cut over. Laravel is the write owner post-cutover.

## Steps
1. **Schema**: run `php artisan migrate` on an empty MySQL 8 (target schema).
2. **Extract** from Supabase/Postgres (read-only) into staging tables/CSV per module.
3. **Transform**:
   - `auth.users` + `profiles` → `users` (map id→uuid, phone from internal email, password hash: Supabase uses bcrypt — rehash on next login or import hash).
   - `grants` → `permission_user` (until, max_amount).
   - UUID columns → char(36); `jsonb` → JSON; numeric preserved.
   - `boxes.sn` sequence → continue auto-increment.
4. **Load** in dependency order: users/roles/permissions → locations → families/items/barcodes → prices → boxes/stock_moves → transfers/trips → shifts/sales/sale_lines → expenses → suppliers/PO/receipts/lots/payments → production → customers/online_orders → settings/audit.
5. **Validate**:
   - Row counts per table (source vs target).
   - Financial totals (Σ sales.total, Σ stock_moves.qty by item/loc).
   - FK integrity (orphans = 0).
   - Permission counts per user.

## Re-runnable scripts
Create `database/seeders/ImportLegacySeeder.php` (or Artisan command `import:legacy`) reading from a sanitized dump — never from prod directly.

## Rollback
Keep Postgres intact until acceptance; revert DNS/connection to Postgres if validation fails.

## Status: ○ planned (not executed — requires prod dump + explicit approval).