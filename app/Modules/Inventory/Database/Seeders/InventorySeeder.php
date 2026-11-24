<?php

namespace App\Modules\Inventory\Database\Seeders;

use App\Models\User;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockCount;
use App\Modules\Inventory\Models\StockCountItem;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $locations = Location::pluck('id')->toArray();
            $items = Item::pluck('id')->toArray();
            $user = User::where('phone', '01000000000')->first();
            $userId = $user?->id;

            // Boxes - create realistic packed boxes
            $boxes = [];
            for ($i = 1; $i <= 500; $i++) {
                $itemId = $items[array_rand($items)];
                $locationId = $locations[array_rand($locations)];
                $status = ['stock', 'stock', 'stock', 'stock', 'loaded', 'sold', 'waste', 'donated'][array_rand([0,1,2,3,4,5,6,7])];

                $boxes[] = [
                    'item_id' => $itemId,
                    'weight' => round(rand(200, 1200) / 1000, 3),
                    'scale_code' => 'SC' . str_pad($i, 6, '0', STR_PAD_LEFT),
                    'packed_by' => $userId,
                    'packed_at' => now()->subDays(rand(0, 30)),
                    'expires_at' => now()->addDays(rand(1, 60)),
                    'location_id' => $locationId,
                    'status' => $status,
                    'crate' => $status === 'loaded' ? 'CRT' . str_pad(rand(1, 50), 3, '0', STR_PAD_LEFT) : null,
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ];
            }

            foreach ($boxes as $box) {
                Box::create($box);
            }

            // Stock Moves - create realistic ledger entries
            $moveKinds = ['pack', 'receive', 'sale', 'transfer', 'adjust', 'waste', 'donate', 'count', 'return', 'production'];
            $reasons = [
                'pack' => ['تغليف طلب إنتاج', 'تغليف مخزون', 'إعادة تغليف'],
                'receive' => ['استلام أمر توريد', 'مرتجع من فرع', 'توريد مباشر'],
                'sale' => ['بيع كاشير', 'بيع أونلاين', 'بيع جملة'],
                'transfer' => ['تحويل بين فروع', 'تحويل للمصنع', 'تحويل للتبرعات'],
                'adjust' => ['جرد دوري', 'تصحيح وزن', 'تلف عبوات'],
                'waste' => ['هالك منتهي الصلاحية', 'هالك تالف', 'عينات مختبر'],
                'donate' => ['تبرع لجمعية', 'تبرع لمستشفى', 'عينات مجانية'],
                'count' => ['جرد سنوي', 'جرد شهري', 'جرد مفاجئ'],
                'return' => ['مرتجع عميل', 'مرتجع فرع', 'منتج خاطئ'],
                'production' => ['إنتاج داخلي', 'مادة أولية مستهلكة', 'فاقد إنتاج'],
            ];

            $refMap = [
                'pack' => 'PRD-',
                'receive' => 'PO-',
                'sale' => 'SO-',
                'transfer' => 'TO-',
                'adjust' => 'ADJ-',
                'waste' => 'WST-',
                'donate' => 'DON-',
                'count' => 'CNT-',
                'return' => 'RET-',
                'production' => 'PRD-',
            ];

            for ($i = 1; $i <= 2000; $i++) {
                $itemId = $items[array_rand($items)];
                $locationId = $locations[array_rand($locations)];
                $kind = $moveKinds[array_rand($moveKinds)];
                $reason = $reasons[$kind][array_rand($reasons[$kind])];
                $refPrefix = $refMap[$kind];
                $ref = $refPrefix . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                // Positive for in, negative for out
                $isIn = in_array($kind, ['pack', 'receive', 'transfer', 'return', 'production']);
                $qty = $isIn ? rand(1, 100) / 10 : -rand(1, 50) / 10;

                StockMove::create([
                    'at' => now()->subDays(rand(0, 60))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
                    'item_id' => $itemId,
                    'location_id' => $locationId,
                    'qty' => round($qty, 3),
                    'kind' => $kind,
                    'reason' => $reason,
                    'ref' => $ref,
                    'box_sn' => $kind === 'pack' || $kind === 'sale' ? Box::inRandomOrder()->value('sn') : null,
                    'unit_cost' => $kind === 'receive' ? rand(1000, 5000) / 100 : null,
                    'by' => $userId,
                ]);
            }

            // Stock Counts - create a couple of counts
            for ($c = 1; $c <= 3; $c++) {
                $locationId = $locations[array_rand($locations)];
                $count = StockCount::create([
                    'location_id' => $locationId,
                    'user_id' => $userId,
                    'count_number' => 'CNT-' . now()->format('YmdHis') . '-' . str_pad($c, 3, '0', STR_PAD_LEFT) . '-' . rand(100, 999),
                    'status' => $c === 3 ? 'in_progress' : 'completed',
                    'approved_by' => $c === 3 ? null : $userId,
                    'approved_at' => $c === 3 ? null : now()->subDays(rand(0, 5)),
                    'notes' => 'جرد مخزون دوري',
                ]);

                // Count items for this location
                $countItems = Item::where('active', true)->take(rand(10, 30))->get();
                foreach ($countItems as $item) {
                    $systemQty = rand(0, 200) / 10;
                    $countedQty = $systemQty + rand(-50, 50) / 100;
                    $countedQty = max(0, $countedQty);
                    $unitCost = rand(1000, 5000) / 100;

                    StockCountItem::create([
                        'stock_count_id' => $count->id,
                        'item_id' => $item->id,
                        'system_quantity' => round($systemQty, 3),
                        'actual_quantity' => round($countedQty, 3),
                        'difference' => round($countedQty - $systemQty, 3),
                        'unit_cost' => $unitCost,
                        'total_cost' => round($unitCost * $countedQty, 2),
                        'notes' => $countedQty !== $systemQty ? 'فرق جرد' : null,
                    ]);
                }
            }
        });
    }
}