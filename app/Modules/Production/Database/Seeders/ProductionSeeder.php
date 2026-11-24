<?php

namespace App\Modules\Production\Database\Seeders;

use App\Models\User;
use App\Modules\Production\Models\ItemRoute;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Production\Models\ProdStep;
use App\Modules\Catalog\Models\Item;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data
            \App\Modules\Production\Models\ProdStep::query()->delete();
            \App\Modules\Production\Models\ProdOrder::query()->delete();

            // Item Routes - define production steps for items
            $productionItems = Item::whereIn('kind', ['semi', 'mix'])->get();
            if ($productionItems->isEmpty()) {
                // Use some regular items as production items
                $productionItems = Item::where('kind', 'product')->take(10)->get();
            }

            $factorySections = Location::where('type', 'factory')->pluck('id')->toArray();
            if (empty($factorySections)) {
                $factorySections = ['factory'];
            }

            // Create item routes
            $stepNames = ['استقبال', 'بسترة', 'تخمر', 'تعبئة', 'تبريد', 'فحص جودة', 'تغليف'];
            
            foreach ($productionItems as $index => $item) {
                $ver = 1;
                foreach ($stepNames as $stepNum => $stepName) {
                    ItemRoute::updateOrCreate(
                        ['item_id' => $item->id, 'ver' => $ver, 'step' => $stepNum + 1],
                        ['section_id' => $factorySections[array_rand($factorySections)]]
                    );
                }
            }

            // Production Orders
            $orderNo = 1;
            $statuses = ['planned', 'running', 'done', 'done', 'cancelled'];
            $user = User::where('phone', '01000000000')->first();
            $userId = $user?->id;
            
            for ($i = 1; $i <= 40; $i++) {
                $item = $productionItems->random();
                $status = $statuses[array_rand($statuses)];
                $qty = rand(100, 1000);
                
                $order = ProdOrder::create([
                    'no' => $orderNo++,
                    'item_id' => $item->id,
                    'qty' => $qty,
                    'due' => now()->addDays(rand(-5, 30))->format('Y-m-d'),
                    'status' => $status,
                    'note' => 'أمر إنتاج رقم ' . $orderNo,
                    'created_by' => $userId,
                ]);

                // Production Steps
                if (in_array($status, ['running', 'done'])) {
                    $routes = ItemRoute::where('item_id', $item->id)->where('ver', 1)->orderBy('step')->get();
                    
                    $completedSteps = $status === 'done' ? $routes->count() : rand(1, $routes->count());
                    $startTime = now()->subDays(rand(0, 10));
                    
                    foreach ($routes->take($completedSteps) as $route) {
                        $inWeight = $status === 'done' ? $qty : rand($qty * 0.8, $qty);
                        $waste = rand(0, 20);
                        $outWeight = max(0, $inWeight - $waste);
                        
                        $step = ProdStep::create([
                            'order_id' => $order->id,
                            'step' => $route->step,
                            'section_id' => $route->section_id,
                            'status' => $route->step <= $completedSteps ? 'done' : 'pending',
                            'in_weight' => $inWeight,
                            'out_weight' => $outWeight,
                            'waste' => $waste,
                            'started_at' => $startTime,
                            'ended_at' => $route->step <= $completedSteps ? $startTime->copy()->addHours(rand(1, 4)) : null,
                            'started_by' => $userId,
                            'ended_by' => $route->step <= $completedSteps ? $userId : null,
                        ]);
                        
                        $startTime = $step->ended_at ?? $startTime->copy()->addHours(1);
                    }
                }
            }
        });
    }
}