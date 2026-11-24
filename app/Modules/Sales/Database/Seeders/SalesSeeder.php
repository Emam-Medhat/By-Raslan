<?php

namespace App\Modules\Sales\Database\Seeders;

use App\Models\User;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleLine;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftExpense;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data in correct order to avoid foreign key constraints
            \App\Modules\Sales\Models\ShiftExpense::query()->delete();
            \App\Modules\Sales\Models\CashTransfer::query()->delete();
            \App\Modules\Sales\Models\SaleLine::query()->delete();
            \App\Modules\Sales\Models\Sale::query()->delete();
            \App\Modules\Sales\Models\Shift::query()->delete();
            
            $locations = Location::where('type', 'dark')->pluck('id')->toArray();
            $items = Item::where('active', true)->pluck('id')->toArray();
            $cashier = User::where('phone', '01000000000')->first();
            $cashierId = $cashier?->id;

            // Create shifts for the last 30 days
            $shiftNo = 1;
            for ($day = 30; $day >= 0; $day--) {
                $date = now()->subDays($day);
                $isToday = $day === 0;
                
                // Morning shift (8 AM - 4 PM)
                $morningShift = Shift::create([
                    'location_id' => $locations[array_rand($locations)],
                    'pos_terminal' => 'POS-01',
                    'cashier' => $cashierId,
                    'opened_at' => $date->copy()->setTime(8, 0),
                    'opening_cash' => rand(500, 2000),
                    'status' => $isToday ? 'open' : 'closed',
                    'open_cashier' => $isToday ? $cashierId : null,
                    'closed_at' => $isToday ? null : $date->copy()->setTime(16, 0),
                    'counted_cash' => $isToday ? null : rand(1000, 5000),
                    'expected_cash' => $isToday ? null : rand(1000, 5000),
                    'close_notes' => $isToday ? null : 'إغلاق وردية طبيعي',
                    'expenses_total' => $isToday ? 0 : rand(50, 300),
                    'sent_to_manager' => $isToday ? 0 : rand(200, 1000),
                    'carryover_to_next_shift' => $isToday ? 0 : rand(100, 500),
                ]);

                // Evening shift (4 PM - 12 AM) - always closed to avoid unique constraint on open_cashier
                $eveningShift = Shift::create([
                    'location_id' => $locations[array_rand($locations)],
                    'pos_terminal' => 'POS-02',
                    'cashier' => $cashierId,
                    'opened_at' => $date->copy()->setTime(16, 0),
                    'opening_cash' => rand(500, 2000),
                    'status' => 'closed',
                    'open_cashier' => null,
                    'closed_at' => $date->copy()->setTime(23, 59),
                    'counted_cash' => rand(1000, 5000),
                    'expected_cash' => rand(1000, 5000),
                    'close_notes' => 'إغلاق وردية طبيعي',
                    'expenses_total' => rand(50, 300),
                    'sent_to_manager' => rand(200, 1000),
                    'carryover_to_next_shift' => rand(100, 500),
                ]);

                // Create sales for each shift
                foreach ([$morningShift, $eveningShift] as $shift) {
                    if ($shift->status === 'open') continue;
                    
                    $numSales = rand(15, 40);
                    for ($s = 1; $s <= $numSales; $s++) {
                        $saleTime = $shift->opened_at->copy()->addMinutes(rand(0, 480));
                        
                        // Pick random items for this sale
                        $saleItems = array_rand(array_flip($items), rand(1, 5));
                        if (!is_array($saleItems)) $saleItems = [$saleItems];
                        
                        $subtotal = 0;
                        $saleLines = [];
                        
                        foreach ($saleItems as $itemId) {
                            $qty = rand(1, 5);
                            $priceRecord = Price::where('item_id', $itemId)
                                ->where('location_id', $shift->location_id)
                                ->where('channel', 'direct')
                                ->first();
                            
                            $unitPrice = $priceRecord ? $priceRecord->price : rand(1500, 6000) / 100;
                            $total = round($unitPrice * $qty, 2);
                            $subtotal += $total;
                            
                            $saleLines[] = [
                                'item_id' => $itemId,
                                'qty' => $qty,
                                'unit_price' => $unitPrice,
                                'total' => $total,
                            ];
                        }
                        
                        $discount = rand(0, 100) > 70 ? round(rand(500, 5000) / 100, 2) : 0;
                        $total = round($subtotal - $discount, 2);
                        
                        $paymentType = rand(1, 100);
                        if ($paymentType <= 60) {
                            $paidCash = $total;
                            $paidCard = 0;
                        } elseif ($paymentType <= 90) {
                            $paidCard = $total;
                            $paidCash = 0;
                        } else {
                            $paidCash = round($total * rand(30, 70) / 100, 2);
                            $paidCard = round($total - $paidCash, 2);
                        }
                        
                        $change = max(0, round($paidCash - ($total - $paidCard), 2));
                        
                        $sale = Sale::create([
                            'no' => $shiftNo++,
                            'location_id' => $shift->location_id,
                            'shift_id' => $shift->id,
                            'cashier' => $shift->cashier,
                            'at' => $saleTime,
                            'subtotal' => $subtotal,
                            'discount' => $discount,
                            'total' => $total,
                            'paid_cash' => $paidCash,
                            'paid_card' => $paidCard,
                            'change' => $change,
                            'idempotency_key' => 'idem_' . str_pad($shiftNo, 10, '0', STR_PAD_LEFT),
                            'request_payload' => ['items' => $saleLines],
                        ]);
                        
                        // Create sale lines
                        $lineNo = 1;
                        foreach ($saleLines as $line) {
                            SaleLine::create([
                                'sale_id' => $sale->id,
                                'line' => $lineNo++,
                                'item_id' => $line['item_id'],
                                'qty' => $line['qty'],
                                'unit_price' => $line['unit_price'],
                                'total' => $line['total'],
                            ]);
                        }
                    }
                }
            }

            // Cash Transfers
            $shifts = Shift::where('status', 'closed')->get();
            $transferTypes = ['to_manager', 'to_next_shift', 'to_safe'];
            
            foreach ($shifts->take(50) as $shift) {
                CashTransfer::create([
                    'from_shift_id' => $shift->id,
                    'to_shift_id' => null,
                    'location_id' => $shift->location_id,
                    'from_user_id' => $shift->cashier,
                    'to_user_id' => '01000000000', // manager
                    'type' => $transferTypes[array_rand($transferTypes)],
                    'amount' => round(rand(500, 3000), 2),
                    'status' => ['pending', 'approved', 'approved', 'approved'][array_rand([0,1,2,3])],
                    'approved_by' => '01000000000',
                    'notes' => 'تسليم نقدية نهاية الوردية',
                ]);
            }

            // Shift Expenses
            $expenseTitles = [
                'صيانة ماكينة القهوة', 'ورق فواتير', 'مستلزمات تنظيف',
                'صيانة ثلاجة العرض', 'كهرباء إضافية', 'تاكسي موظف',
                'صيانة POS', 'مستلزمات تغليف', 'إصلاح باب', 'تنظيف عميق',
            ];
            
            foreach ($shifts->take(30) as $shift) {
                ShiftExpense::create([
                    'shift_id' => $shift->id,
                    'user_id' => $shift->cashier,
                    'location_id' => $shift->location_id,
                    'title' => $expenseTitles[array_rand($expenseTitles)],
                    'amount' => round(rand(50, 500), 2),
                    'expense_date' => $shift->opened_at,
                    'status' => ['pending', 'approved', 'approved', 'approved'][array_rand([0,1,2,3])],
                    'approved_by' => '01000000000',
                    'notes' => 'مصروفات وردية ضرورية',
                ]);
            }
        });
    }
}