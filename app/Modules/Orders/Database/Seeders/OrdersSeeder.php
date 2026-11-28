<?php

namespace App\Modules\Orders\Database\Seeders;

use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\CustomerAddress;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\OnlineOrderBox;
use App\Modules\Orders\Models\OnlineOrderLine;
use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrdersSeeder extends Seeder
{
    /** Box SNs already used as active_box (globally unique column) - never reuse one. */
    protected array $usedBoxSns = [];

    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data
            \App\Modules\Orders\Models\OnlineOrderBox::query()->delete();
            \App\Modules\Orders\Models\OnlineOrderLine::query()->delete();
            \App\Modules\Orders\Models\OnlineOrder::query()->delete();
            \App\Modules\Orders\Models\CustomerAddress::query()->delete();
            \App\Modules\Orders\Models\Customer::query()->delete();

            // Customers
            $customers = [];
            for ($i = 1; $i <= 50; $i++) {
                $customers[] = Customer::updateOrCreate(
                    ['id' => $i],
                    [
                        'auth_id' => 'auth_' . str_pad($i, 10, '0', STR_PAD_LEFT),
                        'phone' => '01' . rand(10, 99) . rand(10000000, 99999999),
                        'name' => 'عميل ' . $i,
                        'address' => 'عنوان العميل ' . $i . '، شارع رئيسي، حي ' . rand(1, 20),
                        'area' => ['رشدي', 'العجمي', 'مصر الجديدة', 'المعادي', 'الإسكندرية', 'القاهرة'][array_rand([0,1,2,3,4,5])],
                        'notes' => 'ملاحظات العميل ' . $i,
                    ]
                );
            }

            // Customer Addresses
            foreach ($customers as $customer) {
                $numAddresses = rand(1, 3);
                for ($a = 1; $a <= $numAddresses; $a++) {
                    CustomerAddress::create([
                        'customer_id' => $customer->id,
                        'label' => ['منزل', 'عمل', 'أخرى'][array_rand([0,1,2])],
                        'area' => $customer->area,
                        'address' => $customer->address . ' - ' . $a,
                        'location_id' => Location::where('type', 'dark')->inRandomOrder()->value('id'),
                        'is_default' => $a === 1,
                        'active' => true,
                    ]);
                }
            }

            // Online Orders
            $items = Item::where('active', true)->pluck('id')->toArray();
            $locations = Location::where('type', 'dark')->pluck('id')->toArray();
            $orderNo = 1;
            $statuses = ['new', 'accepted', 'preparing', 'ready', 'out', 'delivered', 'delivered', 'cancelled'];
            $channels = ['app', 'web', 'phone'];
            $payMethods = ['cash', 'card', 'wallet'];

            for ($i = 1; $i <= 200; $i++) {
                $customer = $customers[array_rand($customers)];
                $address = CustomerAddress::where('customer_id', $customer->id)->where('active', true)->inRandomOrder()->first();
                $status = $statuses[array_rand($statuses)];
                $locationId = $locations[array_rand($locations)];
                
                $numLines = rand(1, 8);
                $selectedItems = array_rand(array_flip($items), $numLines);
                if (!is_array($selectedItems)) $selectedItems = [$selectedItems];
                
                $subtotal = 0;
                $linesData = [];
                
                foreach ($selectedItems as $lineNum => $itemId) {
                    $qty = rand(1, 5);
                    $price = rand(1500, 6000) / 100;
                    $total = round($price * $qty, 2);
                    $subtotal += $total;
                    
                    $linesData[] = [
                        'item_id' => $itemId,
                        'qty' => $qty,
                        'unit_price' => $price,
                        'prepared' => in_array($status, ['preparing', 'ready', 'out', 'delivered']) ? $qty : 0,
                    ];
                }
                
                $deliveryFee = rand(15, 50);
                $total = round($subtotal + $deliveryFee, 2);
                $paid = in_array($status, ['delivered', 'out', 'ready']);
                
                $order = OnlineOrder::create([
                    'no' => $orderNo++,
                    'token' => 'TOK-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'address_id' => $address?->id,
                    'location_id' => $locationId,
                    'channel' => $channels[array_rand($channels)],
                    'from_app' => rand(1, 100) > 30,
                    'status' => $status,
                    'address' => $address?->address ?? $customer->address,
                    'note' => rand(1, 100) > 70 ? 'يرجى الاتصال قبل الوصول' : null,
                    'pay_method' => $payMethods[array_rand($payMethods)],
                    'est_total' => $total,
                    'subtotal' => $subtotal,
                    'delivery_fee' => $deliveryFee,
                    'total' => $total,
                    'paid' => $paid,
                    'driver' => in_array($status, ['out', 'delivered']) ? 'سائق ' . rand(1, 10) : null,
                    'cancel_reason' => $status === 'cancelled' ? ['عميل ألغى', 'عنوان غير صحيح', 'منتج غير متوفر', 'تأخير'][array_rand([0,1,2,3])] : null,
                    'handled_by' => in_array($status, ['accepted', 'preparing', 'ready', 'out', 'delivered']) ? '01000000000' : null,
                    'accepted_at' => in_array($status, ['accepted', 'preparing', 'ready', 'out', 'delivered']) ? now()->subDays(rand(0, 30))->subHours(rand(0, 12)) : null,
                    'ready_at' => in_array($status, ['ready', 'out', 'delivered']) ? now()->subDays(rand(0, 30))->subHours(rand(0, 6)) : null,
                    'out_at' => in_array($status, ['out', 'delivered']) ? now()->subDays(rand(0, 30))->subHours(rand(0, 3)) : null,
                    'delivered_at' => $status === 'delivered' ? now()->subDays(rand(0, 30)) : null,
                ]);
                
                // Order Lines
                foreach ($linesData as $lineNum => $lineData) {
                    OnlineOrderLine::create([
                        'order_id' => $order->id,
                        'line' => $lineNum + 1,
                        'item_id' => $lineData['item_id'],
                        'qty' => $lineData['qty'],
                        'unit_price' => $lineData['unit_price'],
                        'prepared' => $lineData['prepared'],
                    ]);
                }
                
                // Order Boxes (for delivered orders)
                if ($status === 'delivered') {
                    $boxSns = \App\Modules\Inventory\Models\Box::pluck('sn')->toArray();
                    if (empty($boxSns)) {
                        $boxSns = [100001];
                    }
                    // active_box is globally unique: each box SN may be used only once
                    // in the whole seed run, otherwise MySQL throws 1062 Duplicate entry.
                    $available = array_values(array_diff($boxSns, $this->usedBoxSns));
                    $numBoxes = min(rand(1, 5), count($available));
                    for ($b = 0; $b < $numBoxes; $b++) {
                        $boxSn = $available[$b];
                        $this->usedBoxSns[] = $boxSn;
                        OnlineOrderBox::create([
                            'order_id' => $order->id,
                            'box_sn' => $boxSn,
                            'line' => rand(1, count($linesData)),
                            'qty' => rand(1, 3),
                            'removed' => false,
                            'at' => $order->out_at ?? now()->subDays(rand(0, 30)),
                            'active_box' => $boxSn, // unique active box per order
                        ]);
                    }
                }
            }
        });
    }
}