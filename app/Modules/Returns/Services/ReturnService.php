<?php

namespace App\Modules\Returns\Services;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Returns\Models\SalesReturnLine;
use App\Modules\Sales\Models\Sale;
use Illuminate\Support\Facades\DB;

class ReturnService
{
    public function request(Sale $sale, ?string $reason, ?string $by): SalesReturn
    {
        return DB::transaction(function () use ($sale, $reason, $by) {
            $sale->load('lines');

            $return = SalesReturn::create([
                'no' => (int) max(8000, (int) SalesReturn::max('no')) + 1,
                'sale_id' => $sale->id,
                'location_id' => $sale->location_id,
                'status' => 'pending',
                'reason' => $reason,
                'by' => $by,
                'at' => now(),
            ]);

            $sub = 0.0;
            foreach ($sale->lines as $line) {
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

            $return->update(['subtotal' => round($sub, 2), 'total' => round($sub, 2)]);

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

            foreach ($return->lines as $line) {
                if ($line->box_sn !== null) {
                    $box = Box::where('sn', $line->box_sn)->lockForUpdate()->first();

                    if ($box && $box->status === 'sold') {
                        $box->update(['status' => 'stock']);

                        StockMove::create([
                            'item_id' => $box->item_id,
                            'location_id' => $return->location_id,
                            'qty' => abs((float) $box->weight),
                            'kind' => 'return',
                            'ref' => 'R'.$return->no,
                            'box_sn' => $box->sn,
                            'by' => $by,
                        ]);
                    }
                } else {
                    StockMove::create([
                        'item_id' => $line->item_id,
                        'location_id' => $return->location_id,
                        'qty' => (float) $line->qty,
                        'kind' => 'return',
                        'ref' => 'R'.$return->no,
                        'by' => $by,
                    ]);
                }
            }

            $return->update(['status' => 'approved', 'decided_by' => $by, 'decided_at' => now()]);
        });
    }

    public function reject(SalesReturn $return, ?string $by): void
    {
        if ($return->status !== 'pending') {
            throw new \RuntimeException('المرتجع ده مش مستني موافقة');
        }

        $return->update(['status' => 'rejected', 'decided_by' => $by, 'decided_at' => now()]);
    }
}