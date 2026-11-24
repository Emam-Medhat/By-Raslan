<?php

namespace App\Modules\Catalog\Database\Seeders;

use App\Modules\Catalog\Models\Family;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\ItemBarcode;
use App\Modules\Catalog\Models\Price;
use App\Modules\Catalog\Models\PriceLog;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Families
            $families = [
                ['name' => 'جبن'],
                ['name' => 'زبادي'],
                ['name' => 'لبن'],
                ['name' => 'قشطة'],
                ['name' => 'زبدة'],
                ['name' => 'مورتاديلا'],
                ['name' => 'سجق'],
                ['name' => 'لانشون'],
                ['name' => 'بيج بن'],
                ['name' => 'ألبان أطفال'],
            ];

            foreach ($families as $i => $fam) {
                Family::updateOrCreate(
                    ['id' => $i + 1],
                    ['name' => $fam['name']]
                );
            }

            // Items - typical Egyptian dairy products
            $items = [
                // جبن
                ['family_id' => 1, 'name' => 'جبن بيضاء 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1001', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن بيضاء 250جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1002', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن رومي 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1003', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن رومي 250جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1004', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن موزاريلا 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1005', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن شيدر 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1006', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن فيتا 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1007', 'active' => true],
                ['family_id' => 1, 'name' => 'جبن دمياطي 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '1008', 'active' => true],

                // زبادي
                ['family_id' => 2, 'name' => 'زبادي طبيعي 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2001', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي طبيعي 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2002', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي بالفراولة 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2003', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي بالفراولة 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2004', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي بالمانجو 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2005', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي بالمانجو 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2006', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي يوناني 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2007', 'active' => true],
                ['family_id' => 2, 'name' => 'زبادي يوناني 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '2008', 'active' => true],

                // لبن
                ['family_id' => 3, 'name' => 'لبن كامل الدسم 1لتر', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3001', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن كامل الدسم 500مل', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3002', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن خالي الدسم 1لتر', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3003', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن خالي الدسم 500مل', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3004', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن شوكولاتة 1لتر', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3005', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن شوكولاتة 500مل', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3006', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن فراولة 1لتر', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3007', 'active' => true],
                ['family_id' => 3, 'name' => 'لبن فراولة 500مل', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '3008', 'active' => true],

                // قشطة
                ['family_id' => 4, 'name' => 'قشطة 200جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '4001', 'active' => true],
                ['family_id' => 4, 'name' => 'قشطة 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '4002', 'active' => true],
                ['family_id' => 4, 'name' => 'قشطة 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '4003', 'active' => true],

                // زبدة
                ['family_id' => 5, 'name' => 'زبدة 200جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '5001', 'active' => true],
                ['family_id' => 5, 'name' => 'زبدة 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '5002', 'active' => true],
                ['family_id' => 5, 'name' => 'زبدة 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '5003', 'active' => true],

                // مورتاديلا
                ['family_id' => 6, 'name' => 'مورتاديلا 250جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '6001', 'active' => true],
                ['family_id' => 6, 'name' => 'مورتاديلا 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '6002', 'active' => true],
                ['family_id' => 6, 'name' => 'مورتاديلا 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '6003', 'active' => true],

                // سجق
                ['family_id' => 7, 'name' => 'سجق 250جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '7001', 'active' => true],
                ['family_id' => 7, 'name' => 'سجق 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '7002', 'active' => true],
                ['family_id' => 7, 'name' => 'سجق 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '7003', 'active' => true],

                // لانشون
                ['family_id' => 8, 'name' => 'لانشون 250جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '8001', 'active' => true],
                ['family_id' => 8, 'name' => 'لانشون 500جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '8002', 'active' => true],
                ['family_id' => 8, 'name' => 'لانشون 1كيلو', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '8003', 'active' => true],

                // بيج بن
                ['family_id' => 9, 'name' => 'بيج بن دجاج 340جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '9001', 'active' => true],
                ['family_id' => 9, 'name' => 'بيج بن لحم 340جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '9002', 'active' => true],

                // ألبان أطفال
                ['family_id' => 10, 'name' => 'سيريلاك قمح 400جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '10001', 'active' => true],
                ['family_id' => 10, 'name' => 'سيريلاك أرز 400جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '10002', 'active' => true],
                ['family_id' => 10, 'name' => 'نيدو 1+ 400جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '10003', 'active' => true],
                ['family_id' => 10, 'name' => 'نيدو 3+ 400جم', 'kind' => 'product', 'unit' => 'كرتونة', 'scale_plu' => '10004', 'active' => true],
            ];

            foreach ($items as $i => $item) {
                Item::updateOrCreate(
                    ['id' => $i + 1],
                    $item
                );
            }

            // Item Barcodes
            $barcodes = [
                // جبن بيضاء 500جم
                ['item_id' => 1, 'code' => '6281111000015'],
                ['item_id' => 1, 'code' => '6281111000022'],
                // جبن بيضاء 250جم
                ['item_id' => 2, 'code' => '6281111000039'],
                // جبن رومي 500جم
                ['item_id' => 3, 'code' => '6281111000046'],
                // جبن رومي 250جم
                ['item_id' => 4, 'code' => '6281111000053'],
                // زبادي طبيعي 1كيلو
                ['item_id' => 9, 'code' => '6281111000060'],
                // زبادي طبيعي 500جم
                ['item_id' => 10, 'code' => '6281111000077'],
                // لبن كامل الدسم 1لتر
                ['item_id' => 17, 'code' => '6281111000084'],
                // لبن كامل الدسم 500مل
                ['item_id' => 18, 'code' => '6281111000091'],
                // قشطة 200جم
                ['item_id' => 25, 'code' => '6281111000107'],
                // زبدة 200جم
                ['item_id' => 28, 'code' => '6281111000114'],
            ];

            foreach ($barcodes as $bc) {
                ItemBarcode::updateOrCreate(
                    ['item_id' => $bc['item_id'], 'code' => $bc['code']],
                    []
                );
            }

            // Prices - per location and channel
            $locations = ['factory', 'main', 'roushdy', 'agamy', 'masr-gedida', 'maadi', 'donate'];

            $basePrices = [
                1 => 45.00, 2 => 25.00, 3 => 55.00, 4 => 30.00, 5 => 65.00,
                6 => 60.00, 7 => 50.00, 8 => 48.00, 9 => 35.00, 10 => 20.00,
                11 => 38.00, 12 => 22.00, 13 => 40.00, 14 => 24.00, 15 => 36.00,
                16 => 21.00, 17 => 18.00, 18 => 10.00, 19 => 17.00, 20 => 9.50,
                21 => 18.00, 22 => 10.00, 23 => 17.00, 24 => 9.50, 25 => 12.00,
                26 => 25.00, 27 => 45.00, 28 => 15.00, 29 => 30.00, 30 => 55.00,
                31 => 18.00, 32 => 32.00, 33 => 58.00, 34 => 16.00, 35 => 30.00,
                36 => 55.00, 37 => 15.00, 38 => 28.00, 39 => 50.00, 40 => 45.00,
                41 => 50.00, 42 => 52.00, 43 => 55.00,
            ];

            foreach ($locations as $loc) {
                foreach (range(1, 43) as $itemId) {
                    $base = $basePrices[$itemId] ?? 25.00;
                    // Factory gets wholesale price, branches get retail
                    $directPrice = $loc === 'factory' ? $base * 0.85 : $base;
                    $appPrice = $loc === 'factory' ? $base * 0.90 : $base * 1.05;

                    Price::updateOrCreate(
                        ['item_id' => $itemId, 'location_id' => $loc, 'channel' => 'direct'],
                        ['price' => round($directPrice, 2)]
                    );

                    Price::updateOrCreate(
                        ['item_id' => $itemId, 'location_id' => $loc, 'channel' => 'app'],
                        ['price' => round($appPrice, 2)]
                    );
                }
            }

            // Price Log - some history
            for ($i = 1; $i <= 10; $i++) {
                $itemId = rand(1, 43);
                $oldPrice = rand(1500, 5000) / 100;
                $newPrice = $oldPrice + rand(-200, 500) / 100;

                PriceLog::create([
                    'item_id' => $itemId,
                    'old_price' => round($oldPrice, 2),
                    'new_price' => round(max(5, $newPrice), 2),
                    'by' => '01000000000',
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        });
    }
}