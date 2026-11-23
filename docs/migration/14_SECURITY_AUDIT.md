# 14 — Security Audit (Interim)

## Verified fixes in this run
- **POS discount enforcement**: `PosService::sale` now checks `pos.discount` permission and `permissionLimit`; rejects over-limit discounts.
- **POS input validation**: money fields validated for finite/non-negative/2-decimal/max; location must be active `dark/shop`.
- **Lookup parity hardening**: box status messages, inactive item checks, rostos boxed-item rule, invalid box weight/price checks.
- **Locations write permission parity**: create/toggle require `settings.edit` (source parity).
- **Resolve missing box**: restricted to `stock.adjust` route middleware and uses transactional updates.

## Existing controls observed
- Session auth + `auth`, `active`, `perm:*` middleware.
- Login throttling (`throttle:10,1` on login POST) and RateLimiter in LoginController.
- Anti-escalation in `PermissionService::grant`.
- CSRF enabled by web middleware; `/api/*` excluded by design.
- Passwords hashed via Laravel casts.

## Not verified / gaps
- Full RLS parity: Supabase had granular RLS policies per table; Laravel equivalent policies/gates for every model/action not fully audited.
- Branch/data scoping: `home_location_id` exists, but row-level branch isolation across all queries/controllers not fully proven.
- Public/mobile API auth model differs from Supabase Auth/anon RPCs; compatibility layer is partial.
- File upload validation/storage hardening not fully audited.
- No comprehensive penetration/authorization matrix across all routes.
- PostgreSQL-specific security features (RLS, SECURITY DEFINER functions) not reproduced/verified in PG runtime.
