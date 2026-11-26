<?php

namespace App\Modules\Production\Services;

use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Production\Models\ItemRoute;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Production\Models\ProdStep;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    /** Save a new version of an item's room route. @param list<string> $sections */
    public function setRoute(int $itemId, array $sections): int
    {
        if ($sections === []) {
            throw new \RuntimeException('اختار غرفة واحدة على الأقل');
        }

        return DB::transaction(function () use ($itemId, $sections) {
            $ver = (int) ItemRoute::where('item_id', $itemId)->max('ver') + 1;

            $step = 0;
            foreach ($sections as $sectionId) {
                $step++;
                ItemRoute::create(['item_id' => $itemId, 'ver' => $ver, 'step' => $step, 'section_id' => $sectionId]);
            }

            return $ver;
        });
    }

    public function createOrder(int $itemId, float $qty, ?string $due, ?string $note, ?string $by): ProdOrder
    {
        return DB::transaction(function () use ($itemId, $qty, $due, $note, $by) {
            $ver = (int) ItemRoute::where('item_id', $itemId)->max('ver');

            if ($ver < 1) {
                throw new \RuntimeException('المنتج ده مالوش مشوار على الغرف — حدده الأول');
            }

            $order = ProdOrder::create([
                'no' => (int) max(2000, (int) ProdOrder::max('no')) + 1,
                'item_id' => $itemId,
                'qty' => $qty,
                'due' => $due ?? now()->toDateString(),
                'status' => 'planned',
                'note' => $note,
                'created_by' => $by,
            ]);

            foreach (ItemRoute::where('item_id', $itemId)->where('ver', $ver)->orderBy('step')->get() as $route) {
                ProdStep::create([
                    'order_id' => $order->id,
                    'step' => $route->step,
                    'section_id' => $route->section_id,
                    'status' => 'waiting',
                ]);
            }

            return $order;
        });
    }

    public function startStep(int $orderId, int $step, float $inWeight, ?string $by): void
    {
        $prodStep = ProdStep::where('order_id', $orderId)->where('step', $step)->lockForUpdate()->first();

        if (! $prodStep) {
            throw new \RuntimeException('الخطوة دي مش موجودة');
        }
        if ($prodStep->status !== 'waiting') {
            throw new \RuntimeException('الخطوة دي بدأت قبل كده');
        }
        if (ProdStep::where('order_id', $orderId)->where('step', '<', $step)->where('status', '!=', 'done')->exists()) {
            throw new \RuntimeException('الغرفة اللي قبلها لسه ماخلصتش');
        }
        if ($inWeight <= 0) {
            throw new \RuntimeException('اكتب الوزن اللي داخل');
        }

        $prodStep->update([
            'status' => 'running',
            'in_weight' => $inWeight,
            'started_at' => now(),
            'started_by' => $by,
        ]);

        ProdOrder::where('id', $orderId)->where('status', 'planned')->update(['status' => 'running']);
    }

    public function finishStep(int $orderId, int $step, float $out, float $waste, ?string $by): string
    {
        $prodStep = ProdStep::where('order_id', $orderId)->where('step', $step)->lockForUpdate()->first();

        if (! $prodStep || $prodStep->status !== 'running') {
            throw new \RuntimeException('الخطوة دي مش شغالة');
        }
        if ($out <= 0) {
            throw new \RuntimeException('اكتب الوزن الطالع');
        }
        if ($out + $waste > (float) $prodStep->in_weight * 1.05) {
            throw new \RuntimeException('الطالع والهالك أكتر من اللي دخل');
        }

        $prodStep->update([
            'status' => 'done',
            'out_weight' => $out,
            'waste' => $waste,
            'ended_at' => now(),
            'ended_by' => $by,
        ]);

        $next = ProdStep::where('order_id', $orderId)->where('step', '>', $step)->orderBy('step')->first();

        if (! $next) {
            ProdOrder::where('id', $orderId)->update(['status' => 'done']);

            return 'الأمر خلص';
        }

        return (string) $next->section?->name;
    }

    /**
     * Pack one box from a scale label.
     *
     * يقبل باركود ميزان 13 رقماً (كود صنف 6 أرقام + وزن 5 أرقام، انظر
     * ScaleBarcodeParser) أو الصيغة القديمة "itemId|weight".
     * في باركود الميزان يُطابَق الصنف بكود الميزان (items.scale_plu) —
     * فإن لم يوجد صنف بهذا الكود لا تُنشأ علبة.
     *
     * @param string|null $locationId الفرع/الموقع الذي ستذهب إليه العلبة (افتراضي: المصنع)
     * @param string|null $packedAt   تاريخ الإنتاج (افتراضي: الآن)
     * @param string|null $expiresAt  تاريخ الصلاحية (اختياري — لا يُخترع)
     */
    public function packBox(string $code, ?int $orderId, ?string $by, ?string $locationId = null, ?string $packedAt = null, ?string $expiresAt = null): Box
    {
        $parsed = ScaleBarcodeParser::parseOrFail($code);
        $code = trim($code);

        if ($parsed['source'] === 'scale') {
            // المطابقة بكود الميزان فقط — الاسم لا يُطابَق.
            $item = \App\Modules\Catalog\Models\Item::where('scale_plu', $parsed['item_code'])->first();

            // توافق مع أكواد قديمة مخزنة بدون أصفار بادئة (مثل 110 بدل 000110).
            if (! $item && ltrim((string) $parsed['item_code'], '0') !== '') {
                $item = \App\Modules\Catalog\Models\Item::where('scale_plu', ltrim((string) $parsed['item_code'], '0'))->first();
            }

            if (! $item) {
                throw new \RuntimeException('كود الصنف '.$parsed['item_code'].' غير موجود في السيستم - طابق كود الميزان مع كود السيستم');
            }
            if ($item->kind !== 'product') {
                throw new \RuntimeException('ده ملصق مش لعلبة منتج نهائي');
            }

            $itemId = $item->id;
            $weight = $parsed['weight_kg'];
        } else {
            $itemId = (int) $parsed['item_id'];
            $weight = $parsed['weight_kg'];

            $item = \App\Modules\Catalog\Models\Item::find($itemId);
            if (! $item || $item->kind !== 'product') {
                throw new \RuntimeException('ده ملصق مش لعلبة منتج نهائي');
            }
        }

        if ($weight <= 0) {
            throw new \RuntimeException('وزن العلبة غير صحيح');
        }

        // Double-read guard.
        if (Box::where('scale_code', $code)->where('packed_at', '>', now()->subSeconds(10))->exists()) {
            throw new \RuntimeException('السكانر قرا نفس الملصق مرتين');
        }

        if ($orderId) {
            $order = ProdOrder::find($orderId);
            if (! $order) {
                throw new \RuntimeException('أمر الإنتاج ده مش موجود');
            }
            if ($order->item_id !== $itemId) {
                throw new \RuntimeException('الملصق ده لصنف تاني');
            }
            if (! in_array($order->status, ['planned', 'running'], true)) {
                throw new \RuntimeException('الأمر ده مقفول');
            }

            $step = ProdStep::where('order_id', $orderId)->where('status', '!=', 'done')->orderBy('step')->first();
            if (! $step) {
                throw new \RuntimeException('كل خطوات الأمر خلصت');
            }
            if (ProdStep::where('order_id', $orderId)->where('step', '>', $step->step)->exists()) {
                throw new \RuntimeException('الأمر لسه ماوصلش لآخر غرفة');
            }
            if ($step->status === 'waiting') {
                $prev = ProdStep::where('order_id', $orderId)->where('step', '<', $step->step)->orderByDesc('step')->value('out_weight');
                $step->update(['status' => 'running', 'in_weight' => $prev ?? $order->qty, 'started_at' => now(), 'started_by' => $by]);
                ProdOrder::where('id', $orderId)->update(['status' => 'running']);
            }
        }

        // الفرع/الموقع الذي ستذهب إليه العلبة — افتراضياً المصنع.
        $locationId = $locationId ?: 'factory';
        if (! \App\Modules\Organization\Models\Location::where('id', $locationId)->exists()) {
            throw new \RuntimeException('الفرع ده مش موجود');
        }

        try {
            $packedAtValue = $packedAt ? \Carbon\Carbon::parse($packedAt) : now();
            $expiresAtValue = $expiresAt ? \Carbon\Carbon::parse($expiresAt) : null;
        } catch (\Throwable) {
            throw new \RuntimeException('تاريخ الإنتاج أو الصلاحية غير صحيح');
        }

        $box = Box::create([
            'item_id' => $itemId,
            'weight' => $weight,
            'scale_code' => $code,
            'prod_order_id' => $orderId,
            'packed_by' => $by,
            'packed_at' => $packedAtValue,
            'expires_at' => $expiresAtValue,
            'location_id' => $locationId,
            'status' => 'stock',
        ]);

        StockMove::create([
            'item_id' => $itemId,
            'location_id' => $locationId,
            'qty' => $weight,
            'kind' => 'pack',
            'ref' => 'B'.$box->sn,
            'box_sn' => $box->sn,
            'by' => $by,
        ]);

        return $box;
    }

    public function packFinish(int $orderId, ?string $by): string
    {
        $step = ProdStep::where('order_id', $orderId)->where('status', 'running')->orderByDesc('step')->lockForUpdate()->first();

        if (! $step) {
            throw new \RuntimeException('التغليف للأمر ده مش شغال');
        }

        $boxes = Box::where('prod_order_id', $orderId)->get();
        $out = (float) $boxes->sum('weight');

        if ($boxes->isEmpty()) {
            throw new \RuntimeException('لسه مفيش علب على الأمر ده');
        }

        $step->update([
            'status' => 'done',
            'out_weight' => $out,
            'waste' => max((float) $step->in_weight - $out, 0),
            'ended_at' => now(),
            'ended_by' => $by,
        ]);

        ProdOrder::where('id', $orderId)->update(['status' => 'done']);

        return $boxes->count().' علبة · '.round($out, 2).' كجم';
    }
}