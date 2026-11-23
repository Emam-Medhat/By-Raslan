# REMAINING BLOCKERS — BY RASLAN ERP (Laravel)

None of these block day-to-day ERP operation; each needs a decision or external resource.

| # | Item | Why blocked | Requirement to close |
|---|---|---|---|
| 1 | **PostgreSQL→MySQL data import** | No production dump; direct prod access forbidden | Provide a sanitized Postgres dump + approval; run `import:legacy` + reconciliation |
| 2 | **Expo mobile app re-point** | Client code is outside this repo (`mobile/`) | Change Supabase RPC/Auth calls to `/api/*` + OTP/token flow |
| 3 | **Pixel-level visual parity** | Needs page-by-page diff vs `src/app` | UI pass with design reference |
| 4 | **Browser/Dusk tests** | Dusk not installed (no Chrome driver) | Install Dusk + Chromium; write E2E |
| 5 | **Email/SMS notifications** | No credentials/provider chosen | Choose provider + keys |
| 6 | **File uploads (docs bucket)** | Storage target undecided (local vs S3) | Decide + implement signed uploads |
| 7 | **Exports / printable invoices** | Not in legacy scope explicitly | Add if required |
| 8 | **Branch-level row scoping** | Currently query-level | Add global scope + tests |

## DB engine decision
Built on **MySQL 8** per earlier explicit decision. If PostgreSQL parity is required, the DB layer (migrations/models) must be regenerated — flagged for an explicit decision before any data import.