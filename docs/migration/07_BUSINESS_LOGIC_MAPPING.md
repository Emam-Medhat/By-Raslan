# 07 — Business Logic Mapping

## Financial integrity rules preserved
- **Prices are server-side only**: `PosService` reads `prices` (channel `direct`) — never trusts client price.
- **Append-only stock ledger**: `stock_moves` is insert-only; balances are `SUM(qty)`. Corrections use reversing entries (waste/donate/return).
- **Idempotent sales**: `sales.idempotency_key` unique per cashier; replay returns the same sale.
- **Shift cash reconciliation**: `expected = opening_cash + Σ(paid_cash - change) - Σ(shift expenses)`.
- **Discounts**: require `pos.discount` + `permissionLimit('pos.discount')` cap.
- **Expenses**: above `acc.post` cap → `pending`; approval requires `acc.approve` + `acc.approve` cap.
- **Supplier payments**: require `acc.pay` + cap; balance = Σ(receipt accepted×price) − Σ(payments).
- **Transfers**: two ledger lines same ref (out+in); missing boxes flagged.
- **Returns**: approve restocks boxes/items with `kind=return` ledger lines.
- **Row locks**: `lockForUpdate()` on shifts/boxes/POs/orders during mutations.

## POS sale flow (PosService::sale)
1. Lock open shift for cashier → 2. idempotency check → 3. validate lines (box or item+qty) → 4. price from DB → 5. stock availability check → 6. create sale + lines + stock_moves → 7. mark boxes sold → 8. totals/discount/change.

## Differences from legacy (documented)
- QA spec enforcement (`item_spec_versions` checks/certs/temp/shelf) is simplified to weighed/rejected/price in `receive_po`.
- `can_override` auto-approval is manual via OverrideController.
- RLS replaced by Laravel authorization (see security audit).