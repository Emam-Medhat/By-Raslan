<?php

namespace App\Modules\Trips\Services;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Transfers\Models\Transfer;
use App\Modules\Transfers\Models\TransferBox;
use App\Modules\Trips\Models\Crate;
use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripBox;
use App\Modules\Trips\Models\TripCrate;
use Illuminate\Support\Facades\DB;

class TripService
{
    public function create(string $truck, ?string $driver, ?string $by): Trip
    {
        return Trip::create([
            'no' => (int) Trip::max('no') + 1,
            'truck' => $truck,
            'driver' => $driver,
            'status' => 'loading',
            'created_by' => $by,
        ]);
    }

    public function crateBusy(string $crateId, int $tripId): ?int
    {
        return TripBox::query()
            ->join('boxes', 'boxes.sn', '=', 'trip_boxes.box_sn')
            ->where('trip_boxes.crate_id', $crateId)
            ->where('trip_boxes.trip_id', '!=', $tripId)
            ->where('trip_boxes.removed', false)
            ->where('boxes.crate', $crateId)
            ->whereIn('boxes.status', ['loaded', 'transit'])
            ->value('trip_boxes.trip_id');
    }

    public function openCrate(int $tripId, string $crateId, ?string $toLoc): ?string
    {
        $trip = Trip::findOrFail($tripId);

        if ($trip->status !== 'loading') {
            throw new \RuntimeException('الرحلة دي مش بتتحمّل دلوقتي');
        }

        if (! Crate::where('id', $crateId)->where('active', true)->exists()) {
            throw new \RuntimeException('الصندوق مش متسجّل');
        }

        if ($this->crateBusy($crateId, $tripId)) {
            throw new \RuntimeException('الصندوق لسه في رحلة تانية ومانزلش — استخدم صندوق تاني');
        }

        $existing = TripCrate::where('trip_id', $tripId)->where('crate_id', $crateId)->first();

        if ($existing) {
            if ($toLoc && $existing->to_loc !== $toLoc) {
                throw new \RuntimeException('الصندوق ده متحدد لفرع تاني');
            }
            $existing->update(['closed' => false]);

            return $existing->to_loc;
        }

        if (! $toLoc) {
            return null;
        }

        TripCrate::create(['trip_id' => $tripId, 'crate_id' => $crateId, 'to_loc' => $toLoc]);

        return $toLoc;
    }

    public function loadBox(int $tripId, string $crateId, int $sn, ?string $by): string
    {
        $trip = Trip::findOrFail($tripId);

        if ($trip->status !== 'loading') {
            throw new \RuntimeException('العربية خرجت خلاص');
        }

        if (! TripCrate::where('trip_id', $tripId)->where('crate_id', $crateId)->exists()) {
            throw new \RuntimeException('امسح الصندوق الأول');
        }

        $box = Box::find($sn);

        if (! $box) {
            throw new \RuntimeException('العلبة غير متسجّلة');
        }

        if ($box->status === 'loaded') {
            throw new \RuntimeException("العلبة اتحمّلت قبل كده في {$box->crate}");
        }

        if ($box->status !== 'stock' || $box->location_id !== 'factory') {
            throw new \RuntimeException('العلبة مش في مخزون المصنع');
        }

        if ($box->expires_at && $box->expires_at->lt(now()->addDay())) {
            throw new \RuntimeException('صلاحية العلبة قربت تخلص — ماتتحمّلش');
        }

        TripBox::create([
            'trip_id' => $tripId,
            'crate_id' => $crateId,
            'box_sn' => $sn,
            'at' => now(),
            'by' => $by,
        ]);

        $box->update(['status' => 'loaded', 'crate' => $crateId]);

        return (string) ($box->item?->name ?? $sn);
    }

    public function unloadBox(int $tripId, int $sn): void
    {
        $trip = Trip::findOrFail($tripId);

        if ($trip->status !== 'loading') {
            throw new \RuntimeException('العربية خرجت خلاص');
        }

        $tb = TripBox::where('trip_id', $tripId)->where('box_sn', $sn)->where('removed', false)->first();

        if (! $tb) {
            throw new \RuntimeException('العلبة دي مش على الرحلة دي');
        }

        $tb->update(['removed' => true]);
        Box::where('sn', $sn)->where('status', 'loaded')->update(['status' => 'stock', 'crate' => null]);
    }

    /** @return int number of transfers created */
    public function depart(int $tripId, ?string $by): int
    {
        return DB::transaction(function () use ($tripId, $by) {
            $trip = Trip::where('id', $tripId)->lockForUpdate()->firstOrFail();

            if ($trip->status !== 'loading') {
                throw new \RuntimeException('العربية خرجت قبل كده');
            }

            $boxesByDest = [];
            foreach (TripCrate::where('trip_id', $tripId)->get() as $tc) {
                $boxes = TripBox::where('trip_id', $tripId)
                    ->where('crate_id', $tc->crate_id)
                    ->where('removed', false)
                    ->get();

                if ($boxes->isEmpty()) {
                    continue;
                }

                $boxesByDest[$tc->to_loc] = array_merge($boxesByDest[$tc->to_loc] ?? [], $boxes->all());
            }

            if ($boxesByDest === []) {
                throw new \RuntimeException('العربية فاضية — حمّل علب الأول');
            }

            $created = 0;
            foreach ($boxesByDest as $toLoc => $boxes) {
                $transfer = Transfer::create([
                    'no' => (int) Transfer::max('no') + 1,
                    'from_loc' => 'factory',
                    'to_loc' => $toLoc,
                    'status' => 'transit',
                    'reason' => 'رحلة #'.$trip->no.' · '.$trip->truck,
                    'sent_by' => $by,
                    'sent_at' => now(),
                    'trip_id' => $tripId,
                ]);

                foreach ($boxes as $tb) {
                    $box = Box::find($tb->box_sn);
                    TransferBox::create(['transfer_id' => $transfer->id, 'box_sn' => $box->sn, 'state' => 'sent']);

                    StockMove::create([
                        'item_id' => $box->item_id,
                        'location_id' => 'factory',
                        'qty' => -abs((float) $box->weight),
                        'kind' => 'transfer',
                        'reason' => 'رحلة #'.$trip->no,
                        'ref' => 'T'.$transfer->id,
                        'box_sn' => $box->sn,
                        'by' => $by,
                    ]);
                }

                $created++;
            }

            Box::whereIn('sn', TripBox::where('trip_id', $tripId)->where('removed', false)->pluck('box_sn'))
                ->update(['status' => 'transit']);

            $trip->update(['status' => 'out', 'left_at' => now()]);

            return $created;
        });
    }
}