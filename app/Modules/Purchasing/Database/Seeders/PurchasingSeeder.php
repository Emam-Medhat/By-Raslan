<?php

namespace App\Modules\Purchasing\Database\Seeders;

use App\Models\User;
use App\Modules\Purchasing\Models\ItemSpecVersion;
use App\Modules\Purchasing\Models\Lot;
use App\Modules\Purchasing\Models\OverrideRequest;
use App\Modules\Purchasing\Models\PoLine;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Receipt;
use App\Modules\Purchasing\Models\ReceiptCheck;
use App\Modules\Purchasing\Models\ReceiptDoc;
use App\Modules\Purchasing\Models\ReceiptLine;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Catalog\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PurchasingSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data in correct order to avoid foreign key constraints
            \App\Modules\Purchasing\Models\ItemSpecVersion::query()->delete();
            \App\Modules\Purchasing\Models\Lot::query()->delete();
            \App\Modules\Purchasing\Models\OverrideRequest::query()->delete();
            \App\Modules\Purchasing\Models\SupplierPayment::query()->delete();
            \App\Modules\Purchasing\Models\ReceiptCheck::query()->delete();
            \App\Modules\Purchasing\Models\ReceiptDoc::query()->delete();
            \App\Modules\Purchasing\Models\ReceiptLine::query()->delete();
            \App\Modules\Purchasing\Models\Receipt::query()->delete();
            \App\Modules\Purchasing\Models\PoLine::query()->delete();
            \App\Modules\Purchasing\Models\PurchaseOrder::query()->delete();
            \App\Modules\Purchasing\Models\Supplier::query()->delete();

            $user = User::where('phone', '01000000000')->first();
            $userId = $user?->id;
            
            // Suppliers
            $suppliers = [
                ['name' => 'مزارع الألبان المتكاملة', 'phone' => '01234567890', 'terms' => '30 يوم', 'active' => true],
                ['name' => 'شركة الوادي للألبان', 'phone' => '01234567891', 'terms' => '45 يوم', 'active' => true],
                ['name' => 'مصنع الدلتا للجبن', 'phone' => '01234567892', 'terms' => '30 يوم', 'active' => true],
                ['name' => 'شركة النوبة للأغذية', 'phone' => '01234567893', 'terms' => '60 يوم', 'active' => true],
                ['name' => 'مزارع سيناء العضوية', 'phone' => '01234567894', 'terms' => '30 يوم', 'active' => true],
                ['name' => 'شركة الأهرام للتصنيع', 'phone' => '01234567895', 'terms' => '45 يوم', 'active' => true],
                ['name' => 'مصنع الربيع للألبان', 'phone' => '01234567896', 'terms' => '30 يوم', 'active' => true],
                ['name' => 'شركة الساحل الشمالي', 'phone' => '01234567897', 'terms' => '60 يوم', 'active' => true],
            ];

            foreach ($suppliers as $index => $supplier) {
                Supplier::updateOrCreate(
                    ['id' => $index + 1],
                    $supplier
                );
            }

            // Items for purchasing (raw materials)
            $rawItems = Item::whereIn('kind', ['raw', 'semi'])->get();
            if ($rawItems->isEmpty()) {
                $rawItems = Item::where('kind', 'product')->take(15)->get();
            }

            // Purchase Orders
            $poNo = 1;
            $statuses = ['open', 'partial', 'done', 'done', 'cancelled'];
            
            for ($i = 1; $i <= 50; $i++) {
                $supplier = Supplier::inRandomOrder()->first();
                $status = $statuses[array_rand($statuses)];
                
                $po = PurchaseOrder::create([
                    'no' => $poNo++,
                    'supplier_id' => $supplier->id,
                    'status' => $status,
                    'expected' => now()->addDays(rand(-10, 30))->format('Y-m-d'),
                    'note' => 'أمر توريد رقم ' . $poNo,
                    'created_by' => '01000000000',
                ]);

                // PO Lines
                $numLines = rand(3, 10);
                $items = $rawItems->random(min($numLines, $rawItems->count()));
                
                $lineNum = 1;
                foreach ($items as $item) {
                    $qty = rand(500, 5000);
                    $price = rand(500, 3000) / 100;
                    $received = $status === 'done' ? $qty : ($status === 'partial' ? rand(0, $qty) : 0);
                    
                    PoLine::create([
                        'po_id' => $po->id,
                        'line' => $lineNum++,
                        'item_id' => $item->id,
                        'qty' => $qty,
                        'price' => $price,
                        'received' => $received,
                    ]);
                }
            }

            // Receipts
            $purchaseOrders = PurchaseOrder::whereIn('status', ['partial', 'done'])->get();
            $receiptNo = 1;
            
            foreach ($purchaseOrders->take(30) as $po) {
                $receipt = Receipt::create([
                    'no' => $receiptNo++,
                    'po_id' => $po->id,
                    'supplier_id' => $po->supplier_id,
                    'result' => 'ok',
                    'flags' => [],
                    'override_id' => null,
                    'invoice_no' => 'INV-' . str_pad($receiptNo, 6, '0', STR_PAD_LEFT),
                    'by' => '01000000000',
                ]);

                $poLines = $po->lines;
                foreach ($poLines as $poLine) {
                    $qtyToReceive = min($poLine->qty - $poLine->received, rand(100, $poLine->qty));
                    if ($qtyToReceive <= 0) continue;
                    
                    $weighed = $qtyToReceive + rand(-10, 10);
                    $rejected = rand(0, 5);
                    $accepted = max(0, $weighed - $rejected);
                    
                    ReceiptLine::create([
                        'receipt_id' => $receipt->id,
                        'line' => $poLine->line,
                        'item_id' => $poLine->item_id,
                        'weighed' => $weighed,
                        'rejected' => $rejected,
                        'accepted' => $accepted,
                        'price' => $poLine->price,
                        'temp' => rand(2, 8),
                        'expires' => now()->addDays(rand(30, 180))->format('Y-m-d'),
                        'spec_version' => 1,
                        'lot_code' => 'LOT-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
                        'note' => 'استلام طبيعي',
                    ]);
                }
            }

            $receiptIds = \App\Modules\Purchasing\Models\Receipt::pluck('id')->toArray();
            if (empty($receiptIds)) {
                $receiptIds = [1];
            }

            // Receipt Docs
            for ($i = 1; $i <= 20; $i++) {
                ReceiptDoc::create([
                    'receipt_id' => $receiptIds[array_rand($receiptIds)],
                    'item_id' => rand(1, 43),
                    'doc_name' => 'مستند_' . $i . '.pdf',
                    'photo' => 'docs/receipts/doc_' . str_pad($i, 4, '0', STR_PAD_LEFT) . '.pdf',
                    'missing' => false,
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);
            }

            // Receipt Checks
            $checkTypes = ['حرارة', 'رائحة', 'قوام', 'دهن', 'بروتين', 'لون', 'طعم', 'عبوة'];
            for ($i = 1; $i <= 40; $i++) {
                ReceiptCheck::create([
                    'receipt_id' => $receiptIds[array_rand($receiptIds)],
                    'item_id' => rand(1, 43),
                    'check_text' => $checkTypes[array_rand($checkTypes)],
                    'ok' => rand(1, 100) > 10,
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);
            }

            // Lots
            $receiptLines = ReceiptLine::all();
            foreach ($receiptLines->take(50) as $rl) {
                if (empty($rl->lot_code)) continue;
                
                Lot::updateOrCreate(
                    ['code' => $rl->lot_code],
                    [
                        'item_id' => $rl->item_id,
                        'receipt_id' => $rl->receipt_id,
                        'supplier_id' => $rl->receipt->supplier_id ?? 1,
                        'qty' => $rl->accepted,
                        'cost' => $rl->price,
                        'expires' => $rl->expires,
                        'location_id' => 'factory',
                    ]
                );
            }

            // Supplier Payments
            $paymentMethods = ['نقدي', 'تحويل بنكي', 'شيك', 'أمر دفع'];
            for ($i = 1; $i <= 30; $i++) {
                SupplierPayment::create([
                    'no' => $i,
                    'supplier_id' => rand(1, 8),
                    'amount' => rand(10000, 500000),
                    'method' => $paymentMethods[array_rand($paymentMethods)],
                    'ref' => 'REF-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                    'note' => 'دفعة مورد رقم ' . $i,
                    'by' => $userId,
                ]);
            }

            // Override Requests
            $overrideKinds = ['temp', 'expires', 'spec', 'qty', 'price'];
            $overrideStatuses = ['pending', 'approved', 'rejected', 'used'];
            
            for ($i = 1; $i <= 15; $i++) {
                OverrideRequest::create([
                    'kind' => $overrideKinds[array_rand($overrideKinds)],
                    'ref' => 'RCPT-' . str_pad(rand(1, 30), 4, '0', STR_PAD_LEFT),
                    'violations' => ['حرارة أعلى من المسموح', 'انتهاء صلاحية قريب'],
                    'value' => rand(100, 5000) / 100,
                    'reason' => 'ظروف نقل استثنائية',
                    'status' => $overrideStatuses[array_rand($overrideStatuses)],
                    'decided_by' => rand(1, 100) > 20 ? $userId : null,
                    'decided_at' => rand(1, 100) > 20 ? now()->subDays(rand(0, 10)) : null,
                    'decision_note' => 'تمت الموافقة بناء على تقرير الجودة',
                    'created_by' => $userId,
                ]);
            }

            // Item Spec Versions
            foreach ($rawItems->take(10) as $item) {
                ItemSpecVersion::create([
                    'item_id' => $item->id,
                    'version' => 1,
                    'snapshot' => [
                        'fat_min' => rand(30, 50) / 10,
                        'fat_max' => rand(50, 70) / 10,
                        'protein_min' => rand(25, 35) / 10,
                        'temp_max' => rand(4, 8),
                        'shelf_life' => rand(30, 180),
                    ],
                    'approved_by' => '01000000000',
                    'approved_at' => now()->subDays(rand(1, 60)),
                ]);
            }
        });
    }
}