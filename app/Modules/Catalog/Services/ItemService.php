<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ItemService
{
    public function create(array $data, array $barcodes = []): Item
    {
        $data['scale_plu'] = $this->normalizeScalePlu($data['scale_plu'] ?? null);
        $this->assertUniqueScalePlu($data['scale_plu']);

        return DB::transaction(function () use ($data, $barcodes) {
            $item = Item::create($data);
            $this->syncBarcodes($item, $barcodes);

            return $item;
        });
    }

    public function update(Item $item, array $data, array $barcodes = []): Item
    {
        $data['scale_plu'] = $this->normalizeScalePlu($data['scale_plu'] ?? null);
        $this->assertUniqueScalePlu($data['scale_plu'], (int) $item->id);

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

    private function normalizeScalePlu(?string $code): ?string
    {
        $code = trim((string) $code);
        if ($code === '') return null;
        return str_pad(ltrim($code, '0') ?: '0', 6, '0', STR_PAD_LEFT);
    }

    private function assertUniqueScalePlu(?string $code, ?int $exceptId = null): void
    {
        if ($code === null) return;

        $duplicate = Item::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->whereNotNull('scale_plu')
            ->get(['id', 'scale_plu'])
            ->contains(fn (Item $item) => $this->normalizeScalePlu($item->scale_plu) === $code);

        if ($duplicate) {
            throw ValidationException::withMessages([
                'scale_plu' => 'كود الميزان مستخدم لصنف آخر؛ لازم كل كود PLU يطابق صنفًا واحدًا فقط.',
            ]);
        }
    }
}
