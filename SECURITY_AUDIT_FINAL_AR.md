# SECURITY AUDIT (FINAL)

## Implemented & tested
| Control | Implementation | Test |
|---|---|---|
| Authentication | Laravel session guard, bcrypt (`BCRYPT_ROUNDS=12`) | AuthTest |
| Login lockout | `RateLimiter` 5/min + `throttle:10,1` | AuthTest |
| Active account | `EnsureActive` middleware | AuthorizationMatrixTest |
| Authorization per route | `perm:` middleware on every write route | AuthorizationMatrixTest |
| Owner bypass / anti-escalation | `PermissionService::grant` | UserManagementTest |
| Financial caps | discount/expense/payment/shift-close caps | ExpenseTest, PosTest, AuthorizationMatrixTest |
| CSRF | web enabled, `api/*` excluded | ShopTest (API) |
| XSS | Blade escaping | — |
| SQL injection | Eloquent bindings | — |
| Mass assignment | `$fillable` everywhere | — |
| Audit trail | `audit_log` (login, expense) | ReportingTest |

## RLS → Laravel parity
Legacy RLS (`has_perm`, `auth.uid()`) → `perm` middleware + Gates + service checks. Direct DB access is not exposed to users.

## Gaps (tracked)
- Branch-level row scoping is query-level, not a global scope — add `BelongsToBranch` scope where legacy RLS filtered by `home_location_id`.
- Audit not auto-wired for all modules (observers recommended).
- No CSP/HSTS security headers yet.
- No file-upload validation (docs storage not migrated).

## Verdict
Strong baseline; authorization enforced server-side per route + per operation; no known critical auth bypass. Remaining items are hardening, not blockers.