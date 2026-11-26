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
     * @param array<int,array{sale_line_id:int,destination:string,location_id?:string}> $selections
     */
    public function request(Sale $sale, ?string $reason, ?string $by, array $selections = []): SalesReturn
    {
        return DB::transaction(function () use ($sale, $reason, $by, $selections) {
            $sale->load('lines');
            if ($selections === []) {
                // backward compat: return all lines to stock
                foreach ($sale->lines as $l) $selections[] = ['sale_line_id' => $l->id, 'destination' => 'stock'];
            }

            $selectedIds = collect($selections)->pluck('sale_line_id')->map(fn($v)=>(int)$v)->all();
            if (count($selectedIds) !== count(array_unique($selectedIds))) {
                throw new \RuntimeException('تم اختيار نفس الصنف أكثر من مرة.');
            }
            $lines = $sale->lines->whereIn('id', $selectedIds)->keyBy('id');
            if ($lines->count() !== count($selectedIds)) {
                throw new \RuntimeException('بعض الأصناف المختارة لا تتبع الفاتورة.');
            }
            if ($lines->isEmpty()) throw new \RuntimeException('اختر طبقًا أو صنفًا واحدًا على الأقل للإرجاع.');

            // prevent duplicate return for same sale line when already approved/pending? simple guard:
            $already = SalesReturnLine::whereIn('sale_line_id', $selectedIds)
                ->whereHas('salesReturn', fn($q) => $q->whereIn('status', ['pending','approved']))
                ->exists();
            if ($already) throw new \RuntimeException('بعض الأصناف المختارة تم إرجاعها بالفعل أو قيد مراجعة.');

            $destination = collect($selections)->pluck('destination')->unique()->count() === 1 ? (collect($selections)->first()['destination'] ?? 'stock') : 'mixed';
            $destLoc = collect($selections)->pluck('location_id')->filter()->first() ?: $sale->location_id;

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
                'destination' => $destination,
                'destination_location_id' => $destLoc,
                'reason' => $reason,
                'by' => $by,
                'at' => now(),
            ]);

            $sub = 0.0;
            foreach ($selections as $sel) {
                $line = $lines->get((int)$sel['sale_line_id']);
                if (! $line) continue;
                $dest = in_array($sel['destination'] ?? 'stock', ['stock','waste','donation'], true) ? $sel['destination'] : 'stock';
                $lineLocation = $sel['location_id'] ?? $sale->location_id;
                SalesReturnLine::create([
                    'return_id' => $return->id,
                    'sale_line_id' => $line->id,
                    'item_id' => $line->item_id,
                    'box_sn' => $line->box_sn,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'total' => $line->total,
                    'destination' => $dest,
                    'destination_location_id' => $lineLocation,
                ]);
                $sub += (float) $line->total;
            }

            $return->update(['subtotal' => round($sub, 2), 'total' => round($sub, 2)]);
            return $return;
        });
    }

    public function approve(SalesReturn $return, ?string $by): void
    {
        DB::transaction(function () use ($return, $by) {
            $return = SalesReturn::where('id', $return->id)->lockForUpdate()->with('lines')->firstOrFail();
            if ($return->status !== 'pending') throw new \RuntimeException('المرتجع ده مش مستني موافقة');

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
                $dest = $line->destination ?: $return->destination ?: 'stock';
                $destLocation = $line->destination_location_id ?: $return->destination_location_id ?: $return->location_id;

                if ($line->box_sn !== null) {
                    $box = Box::where('sn', $line->box_sn)->lockForUpdate()->first();
                    if (! $box) continue;
                    if ($box->status !== 'sold' || (int) $box->sale_id !== (int) $return->sale_id) {
                        throw new \RuntimeException('الطبق لم يعد مبيعاً على هذه الفاتورة.');
                    }

                    if ($dest === 'waste') {
                        $box->update(['status' => 'waste', 'location_id' => $destLocation, 'returned_at' => now()]);
                        StockMove::create(['item_id'=>$box->item_id,'location_id'=>$destLocation,'qty'=>0,'kind'=>'waste','reason'=>'مرتجع هالك #'.$return->no,'ref'=>'R'.$return->no,'box_sn'=>$box->sn,'by'=>$by]);
                    } elseif ($dest === 'donation') {
                        $box->update(['status' => 'donated', 'location_id' => 'donate', 'returned_at' => now()]);
                        StockMove::create(['item_id'=>$box->item_id,'location_id'=>'donate','qty'=>abs((float)$box->weight),'kind'=>'donate','reason'=>'مرتجع تبرعات #'.$return->no,'ref'=>'R'.$return->no,'box_sn'=>$box->sn,'by'=>$by]);
                    } else {
                        $box->update(['status' => 'stock', 'location_id' => $destLocation, 'returned_at' => now(), 'sale_id' => null, 'sold_at' => null]);
                        StockMove::create(['item_id'=>$box->item_id,'location_id'=>$destLocation,'qty'=>abs((float)$box->weight),'kind'=>'return','reason'=>'مرتجع للمخزون #'.$return->no,'ref'=>'R'.$return->no,'box_sn'=>$box->sn,'by'=>$by]);
                    }
                } else {
                    $kind = $dest === 'waste' ? 'waste' : ($dest === 'donation' ? 'donate' : 'return');
                    $loc = $dest === 'donation' ? 'donate' : $destLocation;
                    $qty = $dest === 'waste' ? 0 : abs((float)$line->qty);
                    StockMove::create(['item_id'=>$line->item_id,'location_id'=>$loc,'qty'=>$qty,'kind'=>$kind,'reason'=>'مرتجع #'.$return->no,'ref'=>'R'.$return->no,'by'=>$by]);
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
        if ($return->status !== 'pending') throw new \RuntimeException('المرتجع ده مش مستني موافقة');
        $return->update(['status' => 'rejected', 'decided_by' => $by, 'decided_at' => now()]);
    }
}
