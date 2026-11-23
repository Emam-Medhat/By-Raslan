# 05 — Permissions Mapping

## Model
- `permissions` (id = key like `pos.sell`, grp, label).
- `roles` + `role_user` + `role_permission` (role templates).
- `permission_user` = direct user grant with `until` (expiry) and `max_amount` (financial cap).
- Owner (`users.is_owner`) bypasses all checks.

## Permission catalog (26 keys, seeded by IdentitySeeder)
| Group | Keys |
|---|---|
| الإعداد | users.manage, settings.edit, audit.view |
| الأصناف | items.edit, prices.edit |
| المخزون | stock.view, stock.edit, stock.adjust, stock.waste, stock.donate |
| الإنتاج | prod.orders, prod.pack |
| التوزيع | trips.load |
| البيع | pos.sell, pos.discount, pos.shift |
| المورد البشرية | hr.view, hr.pay |
| الحسابات | acc.view, acc.post, acc.pay, acc.approve |
| الطلبات | orders.handle, orders.cancel |
| المشتريات | purchase.edit, override.recv |

## Role templates (9, from legacy roles.ts)
cashier, branch, packer, room, online, loader, storekeeper, factory, accountant.

## Enforcement layers
1. **Route middleware** `perm:<key>` (CheckPermission) — blocks by permission.
2. **Gates/Blade** `@can('key')` — UI visibility (owner + granted).
3. **Service-level checks** — anti-escalation on grant, `pos.discount` cap, `acc.post`/`acc.approve`/`acc.pay` amount limits, `pos.shift` for cross-cashier close.
4. **Controller checks** — e.g. order cancel requires `orders.cancel`; dispose requires `stock.waste`/`stock.donate` by kind.

## Legacy → Laravel parity
- `has_perm(p)` → `User::hasPermission(p)`.
- `perm_limit(p)` → `User::permissionLimit(p)` (null = unlimited, value = cap).
- `secure_grant()` → `PermissionService::grant()` (cannot grant a permission you lack; cannot grant `users.manage`).
- RLS `using (has_perm(...))` → `perm` middleware + Policies.

## Branch / data scope
`users.home_location_id` + `locations` tree exist. Branch-scoped filtering is applied in module queries; full per-branch row scoping is applied where the legacy RLS restricted by branch (see `14_SECURITY_AUDIT.md`).