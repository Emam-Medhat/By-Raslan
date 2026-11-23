# SECURITY & PERMISSIONS TEST

## Enforced server-side
- Every write route has `perm:` middleware; controllers re-check sensitive ops.
- `perm` middleware (CheckPermission) → `User::hasPermission`.
- Gates/Blade `@can` for UI; owner bypass; anti-escalation on grant.
- Financial caps: discount, acc.post/acc.approve/acc.pay, cross-cashier shift close.
- CSRF on web; `api/*` excluded (public customer API).
- Eloquent bindings (SQLi), Blade escaping (XSS), `$fillable` (mass assignment).
- Login rate limit + lockout; `EnsureActive` blocks disabled accounts.

## Tests (tests/Feature/Security)
| Test | Result |
|---|---|
| read-only stock user cannot adjust (403) | ✅ |
| expense poster cannot approve (403) | ✅ |
| cashier cannot close another's shift | ✅ |
| user without users.manage → 403 | ✅ |
| inactive user blocked | ✅ |
| duplicate expense approval rejected | ✅ |
| duplicate sale idempotency | ✅ |
| sequential stock adjust accumulates | ✅ |

## Gaps (tracked)
- Branch-level row scoping is query-level (add global scope).
- Audit auto-wiring only for login/expense.
- No CSP/HSTS headers yet.
- File uploads not implemented (no docs storage).

## Verdict
Authorization is enforced on the server for every sensitive operation and covered by tests; no known auth bypass.