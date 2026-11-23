<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Item;
use Illuminate\Support\Facades\DB;

class ItemService
{
    public function create(array $data, array $barcodes = []): Item
    {
        return DB::transaction(function () use ($data, $barcodes) {
            $item = Item::create($data);
            $this->syncBarcodes($item, $barcodes);

            return $item;
        });
    }

    public function update(Item $item, array $data, array $barcodes = []): Item
    {
        return DB::transaction(function () use ($item, $data, $barcodes) {
            $item->update($data);
            $this->syncBarcodes($item, $barcodes);

            return $item;
        });
    }

    private function syncBarcodes(Item $item, array $barcodes): void
    {
        $item->barcodes()->delete();

        foreach (array_filter(array_map('trim', $barcodes)) as $code) {
            $item->barcodes()->firstOrCreate(['code' => $code]);
        }
    }
}