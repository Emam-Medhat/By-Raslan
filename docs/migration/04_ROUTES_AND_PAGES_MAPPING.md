# 04 — Routes & Pages Mapping

All routes are registered per-module under `app/Modules/*/routes/web.php` and auto-loaded by `ModulesServiceProvider`.

## Web pages (Blade)
| Legacy page | Laravel route | Controller | Permission |
|---|---|---|---|
| /login | `login` | Identity\Auth\LoginController | guest |
| /dashboard | `dashboard` | Reporting\DashboardController | auth |
| /statistics | `statistics.index` | Reporting\StatisticsController | acc.view |
| /pos | `pos.index` | Sales\PosController | pos.sell |
| /sales | `sales.index` | Sales\PosController | acc.view |
| /shifts | `shifts.index` | Sales\PosController | acc.view |
| /orders | `orders.index` / `orders.show` | Orders\OrderController | orders.handle |
| /returns | `returns.index` / `returns.create` | Returns\ReturnController | acc.view / pos.shift |
| /stock | `stock.index` | Inventory\StockController | stock.view |
| /stock/dispose | `stock.dispose` | Inventory\StockController | stock.waste/donate |
| /boxes | `boxes.index` | Inventory\BoxController | stock.view |
| /transfers | `transfers.*` | Transfers\TransferController | stock.view/edit |
| /trips | `trips.*` | Trips\TripController | trips.load |
| /purchasing | `purchasing.*` | Purchasing\PurchaseOrderController | stock.view/purchase.edit |
| /purchasing/suppliers | `purchasing.suppliers.*` | Purchasing\SupplierController | stock.view/purchase.edit/acc.pay |
| /purchasing/receipts | `purchasing.receipts.index` | Purchasing\PurchaseOrderController | stock.view |
| /overrides | `overrides.*` | Purchasing\OverrideController | stock.view/acc.approve |
| /production | `production.*` | Production\ProductionController | stock.view/prod.orders/items.edit |
| /pack | `pack.*` | Production\ProductionController | prod.pack |
| /accounts/expenses | `expenses.*` | Accounting\ExpenseController | acc.view/post/approve |
| /items | `items.*` | Catalog\ItemController | stock.view/items.edit |
| /prices | `prices.*` | Catalog\PriceController | prices.edit |
| /locations | `locations.*` | Organization\LocationController | stock.view/edit |
| /users | `users.*` | Identity\UserController | users.manage |
| /settings | `settings.*` | Settings\SettingController | settings.edit |
| /notifications | `notifications.index` | Notifications\NotificationController | auth |
| /audit | `audit.index` | Audit\AuditController | audit.view |

## Public API (mobile)
| Endpoint | Controller |
|---|---|
| GET /api/branches | Orders\PublicOrderController@branches |
| GET /api/menu?location= | @menu |
| POST /api/orders | @place |
| GET /api/track/{token} | @track |
| POST /api/push-token | @pushToken |

All write routes are protected by `perm:*` middleware; controllers additionally validate.