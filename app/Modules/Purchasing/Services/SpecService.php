<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Models\ItemSpecVersion;
use Illuminate\Support\Facades\DB;

class SpecService
{
    public function current(int $itemId): ?ItemSpecVersion
    {
        return ItemSpecVersion::where('item_id', $itemId)->orderByDesc('version')->first();
    }

    /** @param array<string,mixed> $snapshot */
    public function saveVersion(int $itemId, array $snapshot, ?string $by): ItemSpecVersion
    {
        return DB::transaction(function () use ($itemId, $snapshot, $by) {
            $version = (int) ItemSpecVersion::where('item_id', $itemId)->max('version') + 1;

            return ItemSpecVersion::create([
                'item_id' => $itemId,
                'version' => $version,
                'snapshot' => $snapshot,
                'approved_by' => $by,
                'approved_at' => now(),
            ]);
        });
    }
}