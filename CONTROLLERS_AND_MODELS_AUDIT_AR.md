# CONTROLLERS & MODELS AUDIT

## Controllers (all thin; business logic in Services)
| Controller | Module | Notes |
|---|---|---|
| LoginController | Identity | rate limit + audit |
| UserController | Identity | grant/revoke via PermissionService |
| LocationController | Organization | CRUD |
| ItemController / PriceController | Catalog | barcodes, prices |
| StockController / BoxController | Inventory | adjust/dispose/history |
| TransferController | Transfers | send/receive |
| TripController / CrateController | Trips | loading/depart |
| PosController | Sales | open/lookup/search/sale/close |
| ExpenseController / AccountController | Accounting | expenses + daily summary |
| SupplierController / PurchaseOrderController / OverrideController / SpecController | Purchasing | PO/receive/specs |
| ProductionController | Production | routes/orders/steps/pack |
| OrderController / PublicOrderController / ShopController | Orders | staff + public API + shop |
| ReturnController / PurchaseReturnController | Returns | sales + purchase returns |
| SettingController | Settings | key/value |
| AuditController / NotificationController / DashboardController / StatisticsController | — | — |

## Models (all with $fillable, casts, relationships)
- `User` (HasUuids, roles, permissions, effectivePermissions, permissionLimit).
- Domain models per module; relationships defined (`belongsTo`/`hasMany`/`belongsToMany`).
- Append-only models (`StockMove`, `Sale`, `AuditLog`) configured with correct timestamps.

## Findings
- No business logic in Blade; controllers delegate to services.
- No missing controller methods referenced by routes (verified by route:list + smoke tests).
- Mass assignment protected via `$fillable`.

## Note
- No Repositories/DTOs added (not justified at this scale); services are the abstraction layer.