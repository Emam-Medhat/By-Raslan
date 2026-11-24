<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Item;
use App\Modules\Inventory\Models\StockCount;
use App\Modules\Inventory\Models\StockCountItem;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockCountService
{
    /**
     * Create a new stock count for a location.
     */
    public function create(string $locationId, string $userId): StockCount
    {
        return StockCount::create([
            'location_id' => $locationId,
            'user_id' => $userId,
            'count_number' => 'SC-' . date('Ymd') . '-' . str_pad((string) (StockCount::whereDate('created_at', today())->count() + 1), 3, '0', STR_PAD_LEFT),
            'status' => 'draft',
        ]);
    }

    /**
     * Add an item to the stock count with actual quantity.
     */
    public function addItem(StockCount $count, int $itemId, float $actualQty, ?float $unitCost = null): StockCountItem
    {
        // Get system quantity from stock moves
        $systemQty = (float) StockMove::where('item_id', $itemId)
            ->where('location_id', $count->location_id)
            ->sum('qty');

        $diff = $actualQty - $systemQty;

        return StockCountItem::create([
            'stock_count_id' => $count->id,
            'item_id' => $itemId,
            'system_quantity' => $systemQty,
            'actual_quantity' => $actualQty,
            'difference' => $diff,
            'unit_cost' => $unitCost ?? 0,
            'total_cost' => abs($diff) * ($unitCost ?? 0),
        ]);
    }

    /**
     * Update actual quantity for an item.
     */
    public function updateItem(StockCountItem $item, float $actualQty, ?float $unitCost = null): StockCountItem
    {
        $diff = $actualQty - (float) $item->system_quantity;

        $item->update([
            'actual_quantity' => $actualQty,
            'difference' => $diff,
            'unit_cost' => $unitCost ?? $item->unit_cost,
            'total_cost' => abs($diff) * ($unitCost ?? $item->unit_cost),
        ]);

        return $item;
    }

    /**
     * Approve the stock count and create adjustment stock moves.
     */
    public function approve(StockCount $count, string $userId): void
    {
        DB::transaction(function () use ($count, $userId) {
            foreach ($count->items as $item) {
                if ($item->difference != 0) {
                    // Create adjustment stock move
                    StockMove::create([
                        'item_id' => $item->item_id,
                        'location_id' => $count->location_id,
                        'qty' => $item->difference,
                        'kind' => 'adjust',
                        'reason' => "تسوية جرد #{$count->count_number}",
                        'by' => $userId,
                    ]);
                }
            }

            $count->update([
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Get items in a location for stock count.
     */
    public function getLocationItems(string $locationId)
    {
        // Get all items that have stock moves in this location
        $itemIds = StockMove::where('location_id', $locationId)
            ->distinct()
            ->pluck('item_id');

        return Item::whereIn('id', $itemIds)
            ->with(['barcodes'])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get current stock quantity for an item in a location.
     */
    public static function getStockQuantity(int $itemId, string $locationId): float
    {
        return (float) StockMove::where('item_id', $itemId)
            ->where('location_id', $locationId)
            ->sum('qty');
    }
}