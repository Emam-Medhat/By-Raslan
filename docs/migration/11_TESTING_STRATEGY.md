# 11 — Testing Strategy

## Current state
- **48 feature tests, 171 assertions, all passing** on MySQL (MariaDB 11.4 compatible).
- Test DB: `byraslan_test` (isolated), `RefreshDatabase` per test.

## Security suites (added)
- **AuthorizationMatrixTest**: read-only user blocked from write, expense poster cannot approve, cashier cannot close another's shift, no users.manage → 403, inactive user blocked.
- **ConcurrencyTest**: duplicate expense approval rejected, idempotent sale (one sale), ledger accumulation.
- **QaSpecTest**: receiving requires all spec checks; receipt_checks recorded.

## Coverage by module (all ✅)
| Module | Tests |
|---|---|
| Identity | login success/fail, inactive blocked, logout, user view/forbidden, create user, grant/revoke |
| Organization | view/create locations, non-perm forbidden |
| Catalog | item create+barcodes, non-perm forbidden, price save + log |
| Inventory | adjust, balance derived from moves, non-perm forbidden |
| Transfers | send+receive, missing boxes |
| Trips | full loading flow, wrong-location rejected |
| Sales/POS | item sale totals+stock, box sale, idempotency, close shift diff |
| Accounting | owner expense, above-limit pending + approve |
| Purchasing | receive PO → stock+lot, pay supplier |
| Production | route+order steps, full production+packing |
| Orders | public place+deliver, track by token |
| Returns | sale return restocks box |
| Reporting | cross-cutting pages load |

## Still required (per directive)
- **Authorization matrix** (per-role access) — partial (owner + one non-perm case per module).
- **Concurrency** (duplicate sale, concurrent stock, double payment) — not yet.
- **Browser/Dusk** — not set up.
- **Financial reconciliation** across modules — partial.

## Commands
```bash
php artisan test                 # full suite
php artisan test --filter=Pos    # module
php artisan route:list           # route validation
php artisan migrate:fresh --seed # schema validation
```

## Static checks
- `php -l` across `app/` (used during build).
- `composer dump-autoload -o` after adding modules.
- `php artisan route:list` boots the app (catches wiring errors).