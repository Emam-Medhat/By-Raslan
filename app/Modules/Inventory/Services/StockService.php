<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All stock mutations go through here so the append-only ledger invariant
 * (balances are derived, never overwritten) is enforced in one place.
 */
class StockService
{
    /** @return Collection<int, array{item_id:int, location_id:string, qty:float}> */
    public function balance(?string $location = null): Collection
    {
        $query = StockMove::query()
            ->select('item_id', 'location_id', DB::raw('SUM(qty) AS qty'))
            ->groupBy('item_id', 'location_id')
            ->orderBy('item_id');

        if ($location) {
            $query->where('location_id', $location);
        }

        return $query->get()->map(fn ($row) => [
            'item_id' => (int) $row->item_id,
            'location_id' => $row->location_id,
            'qty' => (float) $row->qty,
        ]);
    }

    public function adjust(int $itemId, string $location, float $qty, ?string $note, ?string $by): StockMove
    {
        return StockMove::create([
            'item_id' => $itemId,
            'location_id' => $location,
            'qty' => $qty,
            'kind' => 'adjust',
            'reason' => $note,
            'by' => $by,
        ]);
    }

    public function receive(int $itemId, string $location, float $qty, ?float $unitCost, ?string $ref, ?string $by): StockMove
    {
        return StockMove::create([
            'item_id' => $itemId,
            'location_id' => $location,
            'qty' => abs($qty),
            'kind' => 'receive',
            'ref' => $ref,
            'unit_cost' => $unitCost,
            'by' => $by,
        ]);
    }

    /**
     * Dispose boxes (waste|donate) by serial number.
     *
     * @param  list<int>  $boxSns
     * @throws \RuntimeException
     */
    public function disposeBoxes(string $location, string $kind, array $boxSns, string $reason, ?string $by): int
    {
        if (! in_array($kind, ['waste', 'donate'], true)) {
            throw new \RuntimeException('النوع لازم هالك أو تبرعات');
        }

        return DB::transaction(function () use ($location, $kind, $boxSns, $reason, $by) {
            $boxes = Box::whereIn('sn', $boxSns)
                ->where('location_id', $location)
                ->where('status', 'stock')
                ->lockForUpdate()
                ->get();

            if ($boxes->isEmpty()) {
                throw new \RuntimeException('العلب غير موجودة في هذا الموقع أو غير متاحة');
            }

            foreach ($boxes as $box) {
                StockMove::create([
                    'item_id' => $box->item_id,
                    'location_id' => $location,
                    'qty' => -abs((float) $box->weight),
                    'kind' => $kind,
                    'reason' => $reason,
                    'ref' => 'D'.$box->sn,
                    'box_sn' => $box->sn,
                    'by' => $by,
                ]);

                if ($kind === 'donate') {
                    $box->update(['location_id' => 'donate']);
                    StockMove::create([
                        'item_id' => $box->item_id,
                        'location_id' => 'donate',
                        'qty' => abs((float) $box->weight),
                        'kind' => 'donate',
                        'reason' => $reason,
                        'ref' => 'D'.$box->sn,
                        'box_sn' => $box->sn,
                        'by' => $by,
                    ]);
                } else {
                    $box->update(['status' => 'waste']);
                }
            }

            return $boxes->count();
        });
    }

    public function resolveMissing(int $sn, bool $found, ?string $note, ?string $by): string
    {
        return DB::transaction(function () use ($sn, $found, $note, $by) {
            $tb = DB::table('transfer_boxes as tb')
                ->join('transfers as t', 't.id', '=', 'tb.transfer_id')
                ->where('tb.box_sn', $sn)
                ->where('tb.state', 'missing')
                ->orderByDesc('t.id')
                ->select('tb.transfer_id', 'tb.box_sn', 't.to_loc', 't.no')
                ->lockForUpdate()
                ->first();

            if (! $tb) {
                throw new \RuntimeException("العلبة #{$sn} مش متسجّلة ناقصة");
            }

            $box = Box::where('sn', $sn)->lockForUpdate()->firstOrFail();

            if ($found) {
                DB::table('transfer_boxes')
                    ->where('transfer_id', $tb->transfer_id)
                    ->where('box_sn', $sn)
                    ->update(['state' => 'found']);

                $box->update(['status' => 'stock', 'location_id' => $tb->to_loc]);

                StockMove::create([
                    'item_id' => $box->item_id,
                    'location_id' => $tb->to_loc,
                    'qty' => abs((float) $box->weight),
                    'kind' => 'transfer',
                    'reason' => $note ?: 'اتلقت بعد الاستلام',
                    'ref' => 'T'.$tb->transfer_id,
                    'box_sn' => $sn,
                    'by' => $by,
                ]);

                return 'اتلقت ودخلت المخزون';
            }

            DB::table('transfer_boxes')
                ->where('transfer_id', $tb->transfer_id)
                ->where('box_sn', $sn)
                ->update(['state' => 'lost']);

            $box->update(['status' => 'waste']);

            return 'اتسجّلت فقد';
        });
    }

    public function boxHistory(int $sn): Collection
    {
        return StockMove::with('user')
            ->where('box_sn', $sn)
            ->orderBy('at')
            ->get();
    }
}