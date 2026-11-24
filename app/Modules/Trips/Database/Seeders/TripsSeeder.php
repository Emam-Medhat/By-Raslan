<?php

namespace App\Modules\Trips\Database\Seeders;

use App\Models\User;
use App\Modules\Trips\Models\Crate;
use App\Modules\Trips\Models\Trip;
use App\Modules\Trips\Models\TripBox;
use App\Modules\Trips\Models\TripCrate;
use App\Modules\Inventory\Models\Box;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // Clear existing data
            \App\Modules\Trips\Models\TripBox::query()->delete();
            \App\Modules\Trips\Models\TripCrate::query()->delete();
            \App\Modules\Trips\Models\Trip::query()->delete();

            // Create crates
            for ($i = 1; $i <= 50; $i++) {
                Crate::updateOrCreate(
                    ['id' => 'CRT' . str_pad($i, 3, '0', STR_PAD_LEFT)],
                    ['active' => true]
                );
            }

            $locations = Location::where('type', 'dark')->pluck('id')->toArray();
            $crates = Crate::where('active', true)->pluck('id')->toArray();
            $boxes = Box::where('status', 'stock')->get();
            $user = User::where('phone', '01000000000')->first();
            $userId = $user?->id;

            $tripNo = 1;
            for ($i = 1; $i <= 30; $i++) {
                $status = ['loading', 'out', 'done', 'done'][array_rand([0,1,2,3])];
                
                $trip = Trip::create([
                    'no' => $tripNo++,
                    'truck' => 'شاحنة-' . rand(1, 10),
                    'driver' => 'سائق ' . rand(1, 15),
                    'status' => $status,
                    'created_by' => $userId,
                    'left_at' => $status !== 'loading' ? now()->subDays(rand(0, 30))->subHours(rand(6, 18)) : null,
                ]);

                // Assign crates to trip
                $numCrates = rand(3, 10);
                $selectedCrates = array_rand(array_flip($crates), min($numCrates, count($crates)));
                if (!is_array($selectedCrates)) $selectedCrates = [$selectedCrates];

                foreach ($selectedCrates as $crateId) {
                    $toLoc = $locations[array_rand($locations)];
                    
                    TripCrate::create([
                        'trip_id' => $trip->id,
                        'crate_id' => $crateId,
                        'to_loc' => $toLoc,
                        'closed' => $status === 'done',
                    ]);

                    // Assign boxes to crate
                    $numBoxes = rand(5, 20);
                    $selectedBoxes = $boxes->random(min($numBoxes, $boxes->count()));
                    
                    foreach ($selectedBoxes as $box) {
                        TripBox::create([
                            'trip_id' => $trip->id,
                            'crate_id' => $crateId,
                            'box_sn' => $box->sn,
                            'removed' => false,
                            'at' => now()->subDays(rand(0, 30))->subHours(rand(6, 18)),
                            'by' => $userId,
                        ]);
                        
                        // Update box status and location
                        $box->update([
                            'status' => $status === 'done' ? 'sold' : 'loaded',
                            'location_id' => $toLoc,
                            'crate' => $crateId,
                        ]);
                        
                        $boxes = $boxes->reject(fn($b) => $b->sn === $box->sn);
                    }
                }
            }
        });
    }
}