<?php

namespace App\Modules\Orders\Database\Seeders;

use App\Modules\Orders\Models\Driver;
use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;

/** Two sample drivers per sellable branch, so driver dropdowns are usable in dev. */
class DriversSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Location::whereIn('type', ['dark', 'shop'])->where('active', true)->get() as $location) {
            for ($i = 1; $i <= 2; $i++) {
                Driver::firstOrCreate(
                    ['name' => 'طيار '.$location->name.' '.$i, 'location_id' => $location->id],
                    ['phone' => '01'.rand(10, 99).rand(10000000, 99999999), 'active' => true],
                );
            }
        }
    }
}
