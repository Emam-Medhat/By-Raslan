<?php

namespace App\Modules\Production\Services;

use App\Modules\Catalog\Services\ScaleBarcodeService;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Inventory\Support\BoxUid;
use App\Modules\Organization\Models\Location;
use App\Modules\Production\Models\ItemRoute;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Production\Models\ProdStep;
use Illuminate\Support\Facades\DB;

class ProductionService
{
    public function __construct(private readonly ScaleBarcodeService $scale)
    {
    }

    public function setRoute(int $itemId, array $sections): int
    {
        if ($sections === []) throw new \RuntimeException('اختار غرفة واحدة على الأقل');
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
            if ($ver < 1) throw new \RuntimeException('المنتج ده مالوش مشوار على الغرف — حدده الأول');
            $order = ProdOrder::create([
                'no' => (int) max(2000, (int) ProdOrder::max('no')) + 1,
                'item_id' => $itemId, 'qty' => $qty, 'due' => $due ?? now()->toDateString(),
                'status' => 'planned', 'note' => $note, 'created_by' => $by,
            ]);
            foreach (ItemRoute::where('item_id', $itemId)->where('ver', $ver)->orderBy('step')->get() as $route) {
                ProdStep::create(['order_id' => $order->id, 'step' => $route->step, 'section_id' => $route->section_id, 'status' => 'waiting']);
            }
            return $order;
        });
    }

    public function startStep(int $orderId, int $step, float $inWeight, ?string $by): void
    {
        $prodStep = ProdStep::where('order_id', $orderId)->where('step', $step)->lockForUpdate()->first();
        if (! $prodStep) throw new \RuntimeException('الخطوة دي مش موجودة');
        if ($prodStep->status !== 'waiting') throw new \RuntimeException('الخطوة دي بدأت قبل كده');
        if (ProdStep::where('order_id', $orderId)->where('step', '<', $step)->where('status', '!=', 'done')->exists()) throw new \RuntimeException('الغرفة اللي قبلها لسه ماخلصتش');
        if ($inWeight <= 0) throw new \RuntimeException('اكتب الوزن اللي داخل');
        $prodStep->update(['status' => 'running', 'in_weight' => $inWeight, 'started_at' => now(), 'started_by' => $by]);
        ProdOrder::where('id', $orderId)->where('status', 'planned')->update(['status' => 'running']);
    }

    public function finishStep(int $orderId, int $step, float $out, float $waste, ?string $by): string
    {
        $prodStep = ProdStep::where('order_id', $orderId)->where('step', $step)->lockForUpdate()->first();
        if (! $prodStep || $prodStep->status !== 'running') throw new \RuntimeException('الخطوة دي مش شغالة');
        if ($out <= 0) throw new \RuntimeException('اكتب الوزن الطالع');
        if ($out + $waste > (float) $prodStep->in_weight * 1.05) throw new \RuntimeException('الطالع والهالك أكتر من اللي دخل');
        $prodStep->update(['status' => 'done', 'out_weight' => $out, 'waste' => $waste, 'ended_at' => now(), 'ended_by' => $by]);
        $next = ProdStep::where('order_id', $orderId)->where('step', '>', $step)->orderBy('step')->first();
        if (! $next) { ProdOrder::where('id', $orderId)->update(['status' => 'done']); return 'الأمر خلص'; }
        return (string) $next->section?->name;
    }

    public function packBox(string $code, ?int $orderId, ?string $by, ?string $locationId = null): Box
    {
        $parsed = $this->scale->parse($code);
        $item = $parsed['item'];
        $weight = (float) $parsed['weight'];
        $scaleCode = $parsed['scale_code'];

        $locationId = $locationId ?: 'factory';
        $loc = Location::where('id', $locationId)->where('active', true)->first();
        if (! $loc) throw new \RuntimeException('الفرع/الموقع غير متاح.');

        if ($item->kind !== 'product') throw new \RuntimeException('ده ملصق مش لمنتج نهائي.');
        if ($weight <= 0) throw new \RuntimeException('وزن العلبة غير صحيح.');

        return DB::transaction(function () use ($item, $weight, $scaleCode, $orderId, $by, $locationId) {
            if ($orderId) {
                $order = ProdOrder::lockForUpdate()->find($orderId);
                if (! $order) throw new \RuntimeException('أمر الإنتاج ده مش موجود');
                if ((int) $order->item_id !== (int) $item->id) throw new \RuntimeException('الملصق ده لصنف تاني');
                if (! in_array($order->status, ['planned', 'running'], true)) throw new \RuntimeException('الأمر ده مقفول');
                $step = ProdStep::where('order_id', $orderId)->where('status', '!=', 'done')->orderBy('step')->lockForUpdate()->first();
                if ($step) {
                    if (ProdStep::where('order_id', $orderId)->where('step', '>', $step->step)->exists()) {
                        // allow last stage only if not last? keep old behavior: must be last stage
                        throw new \RuntimeException('الأمر لسه ماوصلش لآخر غرفة');
                    }
                    if ($step->status === 'waiting') {
                        $prev = ProdStep::where('order_id', $orderId)->where('step', '<', $step->step)->orderByDesc('step')->value('out_weight');
                        $step->update(['status' => 'running', 'in_weight' => $prev ?? $order->qty, 'started_at' => now(), 'started_by' => $by]);
                        ProdOrder::where('id', $orderId)->update(['status' => 'running']);
                    }
                }
            }

            $uid = BoxUid::next();
            $producedAt = now();
            $shelfDays = (int) ($item->shelf_life_days ?? 0);
            $expiresAt = $shelfDays > 0 ? $producedAt->copy()->addDays($shelfDays) : null;

            $box = Box::create([
                'uid' => $uid,
                'item_id' => $item->id,
                'weight' => $weight,
                'scale_code' => $scaleCode,
                'prod_order_id' => $orderId,
                'packed_by' => $by,
                'packed_at' => $producedAt,
                'produced_at' => $producedAt,
                'expires_at' => $expiresAt,
                'location_id' => $locationId,
                'source_location_id' => $locationId,
                'status' => 'stock',
                'meta' => ['scale_raw' => $scaleCode],
            ]);

            StockMove::create([
                'item_id' => $item->id,
                'location_id' => $locationId,
                'qty' => $weight,
                'kind' => 'pack',
                'ref' => 'B'.$box->sn,
                'box_sn' => $box->sn,
                'by' => $by,
            ]);

            return $box->load('item', 'location');
        });
    }

    public function packFinish(int $orderId, ?string $by): string
    {
        $step = ProdStep::where('order_id', $orderId)->where('status', 'running')->orderByDesc('step')->lockForUpdate()->first();
        if (! $step) throw new \RuntimeException('التغليف للأمر ده مش شغال');
        $boxes = Box::where('prod_order_id', $orderId)->get();
        $out = (float) $boxes->sum('weight');
        if ($boxes->isEmpty()) throw new \RuntimeException('لسه مفيش علب على الأمر ده');
        $step->update([
            'status' => 'done', 'out_weight' => $out,
            'waste' => max((float) $step->in_weight - $out, 0),
            'ended_at' => now(), 'ended_by' => $by,
        ]);
        ProdOrder::where('id', $orderId)->update(['status' => 'done']);
        return $boxes->count().' علبة · '.round($out, 2).' كجم';
    }
}
