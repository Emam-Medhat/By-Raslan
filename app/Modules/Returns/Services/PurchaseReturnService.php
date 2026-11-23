<?php

namespace App\Modules\Returns\Services;

use App\Modules\Inventory\Models\StockMove;
use App\Modules\Returns\Models\PurchaseReturn;
use App\Modules\Returns\Models\PurchaseReturnLine;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function request(int $supplierId, array $lines, ?string $reason, ?string $by): PurchaseReturn
    {
        return DB::transaction(function () use ($supplierId, $lines, $reason, $by) {
            $return = PurchaseReturn::create([
                'no' => (int) max(6000, (int) PurchaseReturn::max('no')) + 1,
                'supplier_id' => $supplierId,
                'location_id' => 'main',
                'status' => 'pending',
                'reason' => $reason,
                'by' => $by,
                'at' => now(),
            ]);

            $total = 0.0;
            foreach ($lines as $line) {
                PurchaseReturnLine::create([
                    'return_id' => $return->id,
                    'item_id' => (int) $line['item_id'],
                    'qty' => (float) $line['qty'],
                    'cost' => (float) ($line['cost'] ?? 0),
                ]);
                $total += (float) $line['qty'] * (float) ($line['cost'] ?? 0);
            }

            $return->update(['total' => round($total, 2)]);

            return $return;
        });
    }

    public function approve(PurchaseReturn $return, ?string $by): void
    {
        DB::transaction(function () use ($return, $by) {
            $return = PurchaseReturn::where('id', $return->id)->lockForUpdate()->firstOrFail();

            if ($return->status !== 'pending') {
                throw new \RuntimeException('المرتجع ده مش مستني موافقة');
            }

            foreach ($return->lines as $line) {
                StockMove::create([
                    'item_id' => $line->item_id,
                    'location_id' => $return->location_id,
                    'qty' => -abs((float) $line->qty),
                    'kind' => 'return',
                    'ref' => 'PR'.$return->no,
                    'by' => $by,
                ]);
            }

            $return->update(['status' => 'approved', 'decided_by' => $by, 'decided_at' => now()]);
        });
    }
}