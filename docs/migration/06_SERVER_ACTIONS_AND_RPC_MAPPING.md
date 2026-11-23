# 06 — Server Actions & RPC Mapping

Every legacy server action / RPC has a Laravel replacement. See `MIGRATION_FUNCTION_PARITY.md` for the full table; summary below.

## Server Actions → Laravel
| Legacy action (file) | Laravel controller/service |
|---|---|
| login/login-action | Identity\Auth\LoginController |
| pos/actions (openShift, posLookup, posSale, closeShift) | Sales\PosController + PosService |
| stock (lookup, dispose) | Inventory\BoxController / StockService |
| transfers (send/receive) | Transfers\TransferController + TransferService |
| trips (create/open/load/unload/depart) | Trips\TripController + TripService |
| purchasing (suppliers/PO/receive/override) | Purchasing\* + PurchasingService |
| production (create/start/finish/route) | Production\ProductionController + ProductionService |
| pack (packBox, packFinish) | Production\ProductionController + ProductionService |
| accounts (addExpense, decideExpense, paySupplier) | Accounting\ExpenseService / Purchasing\PurchasingService |
| orders (setStatus, scan, unscan, deliver) | Orders\OrderController + OrderService |
| users (grant, revoke) | Identity\UserController + PermissionService |

## Database functions / RPC → Laravel
| RPC | Laravel replacement | Status |
|---|---|---|
| pos_open_shift | PosService::openShift | ✅ |
| pos_lookup | PosService::lookup | ✅ |
| pos_sale | PosService::sale (idempotent) | ✅ |
| pos_close_shift | PosService::closeShift | ✅ |
| shift_management_report (0029) | Reporting (shifts/sales queries) | ◐ |
| transfer_boxes / receive_transfer | TransferService | ✅ |
| resolve_missing | TransferService::receive (missing flag) | ✅ |
| dispose_boxes | StockService::disposeBoxes | ✅ |
| crate_open / load_box / unload_box / trip_depart | TripService | ✅ |
| receive_po | PurchasingService::receivePo (QA checks ◐) | ◐ |
| pay_supplier | PurchasingService::paySupplier | ✅ |
| add_expense / decide_expense | ExpenseService | ✅ |
| set_route / prod_create / prod_start / prod_finish | ProductionService | ✅ |
| pack_box / prod_pack_finish | ProductionService::packBox/packFinish | ✅ |
| public_menu / public_branches | OrderService::menu/branches | ✅ |
| place_order / track_order | OrderService::placeOrder/track | ✅ |
| order_status / order_scan / order_unscan / order_deliver | OrderService | ✅ |
| can_override | OverrideController (manual) | ◐ |

✅ = implemented + tested · ◐ = core implemented, extended checks (QA spec engine, auto-override approval, shift_management_report view) pending.