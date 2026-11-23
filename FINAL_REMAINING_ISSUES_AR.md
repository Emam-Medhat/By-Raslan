# FINAL REMAINING ISSUES

| # | Issue | Severity | Cause / Requirement |
|---|---|---|---|
| 1 | Browser/E2E not run | High | No Chromium/Dusk; install + author E2E |
| 2 | Visual parity not pixel-perfect | Medium | Needs page-by-page design diff |
| 3 | Tailwind/Alpine via CDN | Medium | Switch to Vite build for production |
| 4 | Branch-level row scoping | Medium | Add global scope + tests |
| 5 | Audit only login/expense | Low | Add model observers |
| 6 | No CSP/HSTS headers | Low | Add security middleware |
| 7 | Postgres→MySQL data import | High (for cutover) | Needs prod dump + approval |
| 8 | Expo app re-point | High (for mobile) | Client change to `/api/*` |
| 9 | Email/SMS notifications | Low | Provider + credentials |
| 10 | Exports / printable invoices | Low | Not in legacy scope explicitly |
| 11 | Items/Users edit pages | Low | Currently create+list |
| 12 | Performance not benchmarked | Medium | Run staging benchmark |

## Fixed during audit
- `/production/routes` 404 (route shadowing) — FIXED + covered by RouteSmokeTest.
- POS redesigned (scanner + search + qty + payment panel).

## DB engine decision
Built on MySQL 8 (per earlier explicit decision). PostgreSQL parity is an open decision before data import.

## Bottom line
Core ERP is functional and tested (56 tests / 190 assertions). Remaining items are hardening, E2E, data migration, and mobile re-point — tracked above with clear requirements.