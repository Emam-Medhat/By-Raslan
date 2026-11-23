<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Price;
use App\Modules\Catalog\Models\PriceLog;
use Illuminate\Support\Facades\DB;

class PriceService
{
    /**
     * Upsert the price for (item, location, channel) and record the change
     * in an append-only price log when the value actually changes.
     */
    public function save(int $itemId, string $location, string $channel, float|string $price, ?string $by = null): Price
    {
        return DB::transaction(function () use ($itemId, $location, $channel, $price, $by) {
            $value = (float) $price;

            $existing = Price::where('item_id', $itemId)
                ->where('location_id', $location)
                ->where('channel', $channel)
                ->first();

            if ($existing) {
                $old = (float) $existing->price;
                $existing->update(['price' => $value]);

                if ($old !== $value) {
                    PriceLog::create([
                        'item_id' => $itemId,
                        'old_price' => $old,
                        'new_price' => $value,
                        'by' => $by,
                    ]);
                }

                return $existing;
            }

            $created = Price::create([
                'item_id' => $itemId,
                'location_id' => $location,
                'channel' => $channel,
                'price' => $value,
            ]);

            PriceLog::create([
                'item_id' => $itemId,
                'old_price' => null,
                'new_price' => $value,
                'by' => $by,
            ]);

            return $created;
        });
    }
}