# PAGES & VIEWS AUDIT

All sidebar pages are backed by a controller and a Blade view that renders real data (no mock data).

| Page | Route | View | Data source | Status |
|---|---|---|---|---|
| Dashboard | dashboard | dashboard.blade | Sales/Orders/Expenses/Boxes counts | ✅ |
| Statistics | statistics.index | statistics.index | Sales/Expenses last 7 days | ✅ |
| POS | pos.index | pos/index | shifts + items + prices (live search) | ✅ redesigned |
| Sales | sales.index | sales/index | sales paginated | ✅ |
| Shifts | shifts.index | shifts/index | shifts paginated | ✅ |
| Orders | orders.index/show | orders/index,show | online_orders + lines | ✅ |
| Shop (public) | shop.index | shop/index | branches + menu API | ✅ |
| Track (public) | shop.track | shop/track | order by token | ✅ |
| Returns | returns.* | returns/index,create | sales_returns | ✅ |
| Stock | stock.index/dispose | stock/index,dispose | stock_moves balances | ✅ |
| Boxes | boxes.index | boxes/index | box + history | ✅ |
| Transfers | transfers.* | transfers/index,create,show | transfers + boxes | ✅ |
| Trips | trips.* | trips/index,create,show | trips + crates | ✅ |
| Purchasing | purchasing.* | purchasing/index,create,show,receipts | POs + receipts | ✅ |
| Specs | purchasing.specs.index | purchasing/specs/index | item_spec_versions | ✅ |
| Suppliers | purchasing.suppliers.index | purchasing/suppliers/index | suppliers + balances | ✅ |
| Overrides | overrides.index | overrides/index | override_requests | ✅ |
| Production | production.* | production/index,create,show,routes | prod_orders/steps | ✅ |
| Pack | pack.index | pack/index | prod_orders | ✅ |
| Accounts | accounts.index | accounts/index | daily summary | ✅ |
| Expenses | expenses.index | accounts/expenses/index | expenses | ✅ |
| Items | items.index | items/index | items + barcodes | ✅ |
| Locations | locations.index | locations/index | locations | ✅ |
| Users | users.* | users/index,create | users + perms | ✅ |
| Settings | settings.index | settings/index | settings | ✅ |
| Crates | crates.index | crates/index | crates | ✅ |
| Notifications | notifications.index | notifications/index | notifications | ✅ |
| Audit | audit.index | audit/index | audit_log | ✅ |

## Known gaps
- Items edit page and Users edit page are create+list only (edit via ◐).
- Pixel-level visual parity not completed (see UI_UX_REDESIGN_REPORT_AR.md).
- No browser automation run (no driver) — pages verified via HTTP smoke tests.