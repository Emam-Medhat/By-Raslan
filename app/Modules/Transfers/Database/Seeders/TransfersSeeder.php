<?php

namespace App\Modules\Transfers\Database\Seeders;

use App\Models\User;
use App\Modules\Transfers\Models\Transfer;
use App\Modules\Transfers\Models\TransferBox;
use App\Modules\Inventory\Models\Box;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransfersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data
            \App\Modules\Transfers\Models\TransferBox::query()->delete();
            \App\Modules\Transfers\Models\Transfer::query()->delete();

            $locations = Location::where('type', '!=', 'donation')->pluck('id')->toArray();
            $boxes = Box::where('status', 'stock')->get();
            $user = User::where('phone', '01000000000')->first();
            $userId = $user?->id;

            $transferNo = 1;
            for ($i = 1; $i <= 50; $i++) {
                $fromLoc = $locations[array_rand($locations)];
                $toLoc = $locations[array_rand(array_diff($locations, [$fromLoc]))];
                
                $status = ['transit', 'received', 'received', 'received'][array_rand([0,1,2,3])];
                
                $transfer = Transfer::create([
                    'no' => $transferNo++,
                    'from_loc' => $fromLoc,
                    'to_loc' => $toLoc,
                    'status' => $status,
                    'reason' => ['تحويل مخزون', 'تحويل إنتاج', 'تعبئة طلب', 'إعادة توزيع'][array_rand([0,1,2,3])],
                    'note' => 'تحويل تلقائي بين الفروع',
                    'sent_by' => $userId,
                    'sent_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                    'recv_by' => $status === 'received' ? $userId : null,
                    'recv_at' => $status === 'received' ? now()->subDays(rand(0, 29))->subHours(rand(0, 23)) : null,
                    'trip_id' => null,
                ]);

                // Assign boxes to transfer
                $numBoxes = rand(5, 25);
                $selectedBoxes = $boxes->random(min($numBoxes, $boxes->count()));
                
                foreach ($selectedBoxes as $box) {
                    TransferBox::create([
                        'transfer_id' => $transfer->id,
                        'box_sn' => $box->sn,
                        'state' => $status === 'received' ? 'received' : 'transit',
                    ]);
                    
                    // Update box status
                    $box->update([
                        'status' => $status === 'received' ? 'stock' : 'loaded',
                        'location_id' => $status === 'received' ? $toLoc : $fromLoc,
                    ]);
                    
                    // Remove from available boxes if received
                    if ($status === 'received') {
                        $boxes = $boxes->reject(fn($b) => $b->sn === $box->sn);
                    }
                }
            }
        });
    }
}