<?php

namespace App\Modules\Returns\Services;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Returns\Models\SalesReturnLine;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    /**
     * Request a return for one invoice. A return is only possible after an
     * invoice exists.
     *
     * @param  list<int>  $lineIds  sale line ids to return; empty = whole invoice
     */
    public function request(
        Sale $sale,
        array $lineIds = [],
        ?string $destination = 'stock',
        ?string $locationId = null,
        ?string $refundMethod = null,
        ?string $reason = null,
        ?string $by = null,
    ): SalesReturn {
        $destination ??= 'stock';
        if (! in_array($destination, SalesReturn::DESTINATIONS, true)) {
            throw new \RuntimeException('وجهة المرتجع لازم مخزون أو هالك أو تبرعات');
        }

        $locationId ??= $sale->location_id;
        if (! Location::where('id', $locationId)->where('active', true)->exists()) {
            throw new \RuntimeException('الفرع اللي رجع له المرتجع مش موجود');
        }

        $refundMethod ??= ((float) $sale->paid_cash - (float) $sale->change) > 0 ? 'cash' : 'card';
        if (! in_array($refundMethod, SalesReturn::REFUND_METHODS, true)) {
            throw new \RuntimeException('طريقة الاسترداد لازم كاش أو فيزا أو بدون استرداد');
        }

        return DB::transaction(function () use ($sale, $lineIds, $destination, $locationId, $refundMethod, $reason, $by) {
            $sale->load('lines');

            $lines = $sale->lines;
            if ($lineIds !== []) {
                $lines = $lines->whereIn('id', $lineIds);
                if ($lines->count() !== count(array_unique($lineIds))) {
                    throw new \RuntimeException('في صنف مش من الفاتورة دي');
                }
            }

            if ($lines->isEmpty()) {
                throw new \RuntimeException('الفاتورة دي مفيهاش أصناف');
            }

            // A sale line can only be returned once (pending or approved).
            $already = SalesReturnLine::whereIn('sale_line_id', $lines->pluck('id'))
                ->whereHas('salesReturn', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
                ->pluck('sale_line_id')
                ->all();
            if ($already !== []) {
                throw new \RuntimeException('في أصناف اترجّعت قبل كده من الفاتورة دي');
            }

            $return = SalesReturn::create([
                'no' => (int) max(8000, (int) SalesReturn::max('no')) + 1,
                'sale_id' => $sale->id,
                'location_id' => $locationId,
                'destination' => $destination,
                'refund_method' => $refundMethod,
                'refund_amount' => 0,
                'status' => 'pending',
                'reason' => $reason,
                'by' => $by,
                'at' => now(),
            ]);

            $sub = 0.0;
            foreach ($lines as $line) {
                SalesReturnLine::create([
                    'return_id' => $return->id,
                    'sale_line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'box_sn' => $line->box_sn,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'total' => $line->total,
                ]);
                $sub += (float) $line->total;
            }

            // The refund never exceeds what is still un-refunded on this sale.
            $alreadyRefunded = (float) SalesReturn::where('sale_id', $sale->id)
                ->where('status', 'approved')
                ->sum('refund_amount');
            $refund = $refundMethod === 'none' ? 0.0 : max(0.0, min(round($sub, 2), round((float) $sale->total - $alreadyRefunded, 2)));

            $return->update(['subtotal' => round($sub, 2), 'total' => round($sub, 2), 'refund_amount' => $refund]);

            return $return;
        });
    }

    public function approve(SalesReturn $return, ?string $by): void
    {
        DB::transaction(function () use ($return, $by) {
            $return = SalesReturn::where('id', $return->id)->lockForUpdate()->firstOrFail();

            if ($return->status !== 'pending') {
                throw new \RuntimeException('المرتجع ده مش مستني موافقة');
            }

            $destination = $return->destination;

            // The refund is charged against the approver's open shift in the
            // return branch, so the shift's expected custody drops by the
            // refunded cash. No open shift = refund recorded on the return
            // only (still visible in the daily closing report).
            $shiftId = null;
            if ($by !== null) {
                $shiftId = Shift::where('cashier', $by)
                    ->where('location_id', $return->location_id)
                    ->where('status', 'open')
                    ->value('id');
            }
            $return->shift_id = $shiftId;

            foreach ($return->lines as $line) {
                if ($line->box_sn !== null) {
                    $box = Box::where('sn', $line->box_sn)->lockForUpdate()->first();

                    if ($box && $box->status === 'sold') {
                        $this->applyBoxDestination($box, $destination, $return, $by);
                    }
                } else {
                    // Loose-qty line (no tray): only a stock return adds
                    // quantity back; waste/donated trays are simply gone.
                    if ($destination === 'stock') {
                        StockMove::create([
                            'item_id' => $line->item_id,
                            'location_id' => $return->location_id,
                            'qty' => (float) $line->qty,
                            'kind' => 'return',
                            'ref' => 'R'.$return->no,
                            'by' => $by,
                            'shift_id' => $return->shift_id,
                        ]);
                    } elseif ($destination === 'donated') {
                        StockMove::create([
                            'item_id' => $line->item_id,
                            'location_id' => 'donate',
                            'qty' => (float) $line->qty,
                            'kind' => 'donate',
                            'reason' => $return->reason ?: 'مرتجع تبرع',
                            'ref' => 'R'.$return->no,
                            'by' => $by,
                            'shift_id' => $return->shift_id,
                        ]);
                    } else {
                        StockMove::create([
                            'item_id' => $line->item_id,
                            'location_id' => $return->location_id,
                            'qty' => 0,
                            'kind' => 'waste',
                            'reason' => $return->reason ?: 'مرتجع هالك',
                            'ref' => 'R'.$return->no,
                            'by' => $by,
                            'shift_id' => $return->shift_id,
                        ]);
                    }
                }
            }

            $return->update([
                'status' => 'approved',
                'decided_by' => $by,
                'decided_at' => now(),
                'shift_id' => $shiftId,
            ]);
        });
    }

    /**
     * Move one sold tray to the return's destination.
     *
     * - stock: back to the return branch's stock, resellable. The ledger
     *   gains the tray weight back (it was deducted at sale time).
     * - waste: status 'waste' in the return branch. No ledger quantity:
     *   the tray was already deducted at sale and is now gone.
     * - donated: status 'donated', moved to the donations location
     *   ('donate', same convention as StockService::disposeBoxes), and
     *   the donations stock gains the weight.
     *
     * No 'returned' status is introduced: the destination lives on the
     * return record and in the tray's StockMove history (ref R{no}).
     */
    private function applyBoxDestination(Box $box, string $destination, SalesReturn $return, ?string $by): void
    {
        if ($destination === 'stock') {
            $box->update(['status' => 'stock', 'location_id' => $return->location_id]);

            StockMove::create([
                'item_id' => $box->item_id,
                'location_id' => $return->location_id,
                'qty' => abs((float) $box->weight),
                'kind' => 'return',
                'ref' => 'R'.$return->no,
                'box_sn' => $box->sn,
                'by' => $by,
                'shift_id' => $return->shift_id,
            ]);

            return;
        }

        if ($destination === 'waste') {
            $box->update(['status' => 'waste', 'location_id' => $return->location_id]);

            StockMove::create([
                'item_id' => $box->item_id,
                'location_id' => $return->location_id,
                'qty' => 0,
                'kind' => 'waste',
                'reason' => $return->reason ?: 'مرتجع هالك',
                'ref' => 'R'.$return->no,
                'box_sn' => $box->sn,
                'by' => $by,
                'shift_id' => $return->shift_id,
            ]);

            return;
        }

        // donated
        $box->update(['status' => 'donated', 'location_id' => 'donate']);

        StockMove::create([
            'item_id' => $box->item_id,
            'location_id' => 'donate',
            'qty' => abs((float) $box->weight),
            'kind' => 'donate',
            'reason' => $return->reason ?: 'مرتجع تبرع',
            'ref' => 'R'.$return->no,
            'box_sn' => $box->sn,
            'by' => $by,
            'shift_id' => $return->shift_id,
        ]);
    }

    public function reject(SalesReturn $return, ?string $by): void
    {
        if ($return->status !== 'pending') {
            throw new \RuntimeException('المرتجع ده مش مستني موافقة');
        }

        $return->update(['status' => 'rejected', 'decided_by' => $by, 'decided_at' => now()]);
    }
}
