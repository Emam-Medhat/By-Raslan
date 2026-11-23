# Migration Function Parity

| Original Function | Purpose | Laravel Replacement | Status | Test |
|---|---|---|---|---|
| pos_open_shift | open shift | PosService::openShift | ✅ | PosTest |
| pos_lookup | scan lookup | PosService::lookup | ✅ | PosTest |
| pos_sale | idempotent sale | PosService::sale | ✅ | PosTest |
| pos_close_shift | close + reconcile | PosService::closeShift | ✅ | PosTest |
| shift_management_report | shift report | Reporting (shifts/sales) | ◐ | ReportingTest |
| transfer_boxes | send transfer | TransferService::send | ✅ | TransferTest |
| receive_transfer | receive + missing | TransferService::receive | ✅ | TransferTest |
| resolve_missing | found/lost box | TransferService::receive | ✅ | TransferTest |
| dispose_boxes | waste/donate | StockService::disposeBoxes | ✅ | InventoryTest |
| crate_open | open crate | TripService::openCrate | ✅ | TripTest |
| load_box | load box | TripService::loadBox | ✅ | TripTest |
| unload_box | unload box | TripService::unloadBox | ✅ | TripTest |
| trip_depart | depart→transfers | TripService::depart | ✅ | TripTest |
| receive_po | receive PO→lots | PurchasingService::receivePo | ✅ | PurchasingTest |
| pay_supplier | supplier payment | PurchasingService::paySupplier | ✅ | PurchasingTest |
| add_expense | add expense | ExpenseService::add | ✅ | ExpenseTest |
| decide_expense | approve/reject | ExpenseService::decide | ✅ | ExpenseTest |
| set_route | item room route | ProductionService::setRoute | ✅ | ProductionTest |
| prod_create | create order | ProductionService::createOrder | ✅ | ProductionTest |
| prod_start/finish | step start/finish | ProductionService::startStep/finishStep | ✅ | ProductionTest |
| pack_box | pack box | ProductionService::packBox | ✅ | ProductionTest |
| prod_pack_finish | finish packing | ProductionService::packFinish | ✅ | ProductionTest |
| public_menu | customer menu | OrderService::menu | ✅ | OrderTest |
| public_branches | branches | OrderService::branches | ✅ | OrderTest |
| place_order | place order | OrderService::placeOrder | ✅ | OrderTest |
| track_order | track by token | OrderService::track | ✅ | OrderTest |
| order_status | status transitions | OrderService::setStatus | ✅ | OrderTest |
| order_scan/unscan | prepare boxes | OrderService::scan/unscan | ✅ | OrderTest |
| order_deliver | deliver+collect | OrderService::deliver | ✅ | OrderTest |
| can_override | override approval | OverrideController (manual) | ◐ | — |
| secure_grant | anti-escalation grant | PermissionService::grant | ✅ | UserManagementTest |
| perm_limit | amount cap | User::permissionLimit | ✅ | ExpenseTest |

✅ tested · ◐ core done, extended logic pending.