# PERFORMANCE AUDIT

## Status: NOT BENCHMARKED (○)
No load/profiling run (no production-like dataset, no profiler). Not claimed.

## Measures in place
- Indexes on FKs, status, `(item_id, location_id)`, unique business keys.
- Eager loading in lists (`with`, `withCount`), pagination (20–30/page).
- Append-only ledger + `SUM(qty)` (no mutable balance row to contend on).
- Queue for Expo push (`ShouldQueue`).
- Per-request settings cache.

## Recommended before production
- Redis for cache/session/queue; run Horizon worker.
- `config:cache`, `route:cache`, `view:cache`, opcache.
- Debugbar in staging to catch N+1 on dashboard/statistics/reports.
- Index review after data import (real cardinalities).
- CDN for static assets; build Tailwind via Vite (currently CDN).

## Known risk
- Tailwind Play CDN + Alpine CDN used in layout (dev). Must switch to compiled assets (Vite) for production.

## Verdict
No measured SLA; treat performance as NOT VERIFIED until a staging benchmark exists.