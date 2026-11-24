<?php

namespace App\Modules\Returns\Database\Seeders;

use App\Modules\Returns\Models\PurchaseReturn;
use App\Modules\Returns\Models\PurchaseReturnLine;
use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Returns\Models\SalesReturnLine;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleLine;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReturnsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $locations = Location::where('type', 'dark')->pluck('id')->toArray();
            $items = Item::where('active', true)->pluck('id')->toArray();

            // Sales Returns
            $sales = Sale::with('lines')->get();
            $returnNo = 1;
            $statuses = ['pending', 'approved', 'rejected'];

            foreach ($sales->take(40) as $sale) {
                $status = $statuses[array_rand($statuses)];
                $saleLines = $sale->lines;
                
                if ($saleLines->isEmpty()) continue;
                
                $numReturnLines = rand(1, min(3, $saleLines->count()));
                $selectedLines = $saleLines->random($numReturnLines);
                
                $subtotal = 0;
                $returnLinesData = [];
                
                foreach ($selectedLines as $saleLine) {
                    $returnQty = rand(1, $saleLine->qty);
                    $total = round($saleLine->unit_price * $returnQty, 2);
                    $subtotal += $total;
                    
                    $returnLinesData[] = [
                        'sale_line_id' => $saleLine->id,
                        'item_id' => $saleLine->item_id,
                        'box_sn' => $saleLine->box_sn,
                        'qty' => $returnQty,
                        'unit_price' => $saleLine->unit_price,
                        'total' => $total,
                    ];
                }
                
                $total = $subtotal; // No additional discount for returns
                
                $return = SalesReturn::create([
                    'no' => $returnNo++,
                    'sale_id' => $sale->id,
                    'location_id' => $sale->location_id,
                    'subtotal' => $subtotal,
                    'total' => $total,
                    'status' => $status,
                    'reason' => ['منتج تالف', 'خطأ في الطلب', 'عميل غير راضٍ', 'منتج منتهي الصلاحية', 'اختلاف مواصفات'][array_rand([0,1,2,3,4])],
                    'by' => '01000000000',
                    'at' => $sale->at->copy()->addDays(rand(0, 7)),
                    'decided_by' => $status !== 'pending' ? '01000000000' : null,
                    'decided_at' => $status !== 'pending' ? now()->subDays(rand(0, 5)) : null,
                ]);
                
                foreach ($returnLinesData as $lineData) {
                    SalesReturnLine::create(array_merge($lineData, ['return_id' => $return->id]));
                }
            }

            // Purchase Returns
            $suppliers = Supplier::where('active', true)->get();
            $purchaseReturnNo = 1;
            
            for ($i = 1; $i <= 20; $i++) {
                $supplier = $suppliers->random();
                $status = $statuses[array_rand($statuses)];
                $locationId = $locations[array_rand($locations)];
                
                $numLines = rand(1, 5);
                $selectedItems = array_rand(array_flip($items), $numLines);
                if (!is_array($selectedItems)) $selectedItems = [$selectedItems];
                
                $total = 0;
                $linesData = [];
                
                foreach ($selectedItems as $itemId) {
                    $qty = rand(10, 200);
                    $cost = rand(500, 3000) / 100;
                    $lineTotal = round($qty * $cost, 2);
                    $total += $lineTotal;
                    
                    $linesData[] = [
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'cost' => $cost,
                    ];
                }
                
                $return = PurchaseReturn::create([
                    'no' => $purchaseReturnNo++,
                    'supplier_id' => $supplier->id,
                    'location_id' => $locationId,
                    'total' => $total,
                    'status' => $status,
                    'reason' => ['مواصفات غير مطابقة', 'كمية زائدة', 'منتج تالف', 'انتهاء صلاحية قريب', 'خطأ في الفاتورة'][array_rand([0,1,2,3,4])],
                    'by' => '01000000000',
                    'at' => now()->subDays(rand(0, 30)),
                    'decided_by' => $status !== 'pending' ? '01000000000' : null,
                    'decided_at' => $status !== 'pending' ? now()->subDays(rand(0, 10)) : null,
                ]);
                
                foreach ($linesData as $lineData) {
                    PurchaseReturnLine::create(array_merge($lineData, ['return_id' => $return->id]));
                }
            }
        });
    }
}