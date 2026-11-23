# MODULE INTEGRATION TEST REPORT

Cross-module workflows verified by feature tests (not isolated):

| Workflow | Modules | Test | Result |
|---|---|---|---|
| Create item → barcode → price | Catalog | CatalogTest | ✅ |
| Purchase order → receive → lots + stock + supplier balance | Purchasing → Inventory → Accounting | PurchasingTest | ✅ |
| Receive with QA checks → receipt_checks | Purchasing (QA) | QaSpecTest | ✅ |
| Production route → order → steps → packing → boxes | Production → Inventory | ProductionTest | ✅ |
| Trip loading → depart → transfers per branch | Trips → Transfers → Inventory | TripTest | ✅ |
| POS sale → stock deduction → shift close | Sales → Inventory → Accounting | PosTest | ✅ |
| Online order → deliver → boxes sold + stock out | Orders → Inventory | OrderTest | ✅ |
| Sales return → restock | Returns → Inventory | ReturnTest | ✅ |
| Purchase return → stock out | Returns → Inventory | PurchaseReturnTest | ✅ |
| Expense → shift cash reconciliation | Accounting → Sales | ExpenseTest | ✅ |

## Key integrity guarantees proven
- Idempotent sale (one row on replay) — ConcurrencyTest.
- Duplicate expense approval rejected — ConcurrencyTest.
- Shift close expected = opening + (cash − change) − shift expenses — PosTest.
- No sale without stock / no negative on oversell — PosTest.

## Verdict
Modules are integrated and transactional; cross-module effects are asserted in DB, not just UI.