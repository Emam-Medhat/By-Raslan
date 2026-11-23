<?php

namespace App\Modules\Transfers\Services;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Transfers\Models\Transfer;
use App\Modules\Transfers\Models\TransferBox;
use Illuminate\Support\Facades\DB;

class TransferService
{
    /**
     * Create a transit transfer of the given boxes from -> to.
     *
     * @param  list<int>  $boxSns
     * @throws \RuntimeException
     */
    public function send(string $from, string $to, array $boxSns, ?string $reason, ?string $note, ?string $by): Transfer
    {
        if ($from === $to) {
            throw new \RuntimeException('من وإلى لازم يكونوا مختلفين');
        }

        $boxSns = array_values(array_unique(array_map('intval', $boxSns)));

        if ($boxSns === []) {
            throw new \RuntimeException('امسح العلب الأول');
        }

        return DB::transaction(function () use ($from, $to, $boxSns, $reason, $note, $by) {
            $boxes = Box::whereIn('sn', $boxSns)
                ->where('location_id', $from)
                ->where('status', 'stock')
                ->lockForUpdate()
                ->get();

            if ($boxes->count() !== count($boxSns)) {
                throw new \RuntimeException('فيه علبة مش في الموقع ده أو مش متاحة');
            }

            $transfer = Transfer::create([
                'no' => (int) Transfer::max('no') + 1,
                'from_loc' => $from,
                'to_loc' => $to,
                'status' => 'transit',
                'reason' => $reason,
                'note' => $note,
                'sent_by' => $by,
                'sent_at' => now(),
            ]);

            foreach ($boxes as $box) {
                TransferBox::create(['transfer_id' => $transfer->id, 'box_sn' => $box->sn, 'state' => 'sent']);

                StockMove::create([
                    'item_id' => $box->item_id,
                    'location_id' => $from,
                    'qty' => -abs((float) $box->weight),
                    'kind' => 'transfer',
                    'reason' => $reason,
                    'ref' => 'T'.$transfer->id,
                    'box_sn' => $box->sn,
                    'by' => $by,
                ]);

                $box->update(['status' => 'transit']);
            }

            return $transfer;
        });
    }

    /**
     * Receive a transfer; boxes not scanned are flagged missing.
     *
     * @param  list<int>  $scanned
     * @return int number of missing boxes
     * @throws \RuntimeException
     */
    public function receive(int $id, array $scanned, ?string $by): int
    {
        $scanned = array_values(array_unique(array_map('intval', $scanned)));

        return DB::transaction(function () use ($id, $scanned, $by) {
            $transfer = Transfer::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== 'transit') {
                throw new \RuntimeException('التحويل ده اتسلّم قبل كده');
            }

            $validSn = $transfer->boxes()->pluck('box_sn')->map(fn ($sn) => (int) $sn)->all();

            foreach ($scanned as $sn) {
                if (! in_array($sn, $validSn, true)) {
                    throw new \RuntimeException('فيه علبة مش من التحويل ده');
                }
            }

            foreach ($transfer->boxes as $tb) {
                if (in_array((int) $tb->box_sn, $scanned, true)) {
                    $tb->update(['state' => 'received']);

                    $box = Box::find($tb->box_sn);
                    $box->update(['status' => 'stock', 'location_id' => $transfer->to_loc]);

                    StockMove::create([
                        'item_id' => $box->item_id,
                        'location_id' => $transfer->to_loc,
                        'qty' => abs((float) $box->weight),
                        'kind' => 'transfer',
                        'ref' => 'T'.$transfer->id,
                        'box_sn' => $box->sn,
                        'by' => $by,
                    ]);
                } else {
                    $tb->update(['state' => 'missing']);
                    Box::where('sn', $tb->box_sn)->update(['status' => 'missing']);
                }
            }

            $transfer->update(['status' => 'received', 'recv_by' => $by, 'recv_at' => now()]);

            return $transfer->boxes()->where('state', 'missing')->count();
        });
    }
}