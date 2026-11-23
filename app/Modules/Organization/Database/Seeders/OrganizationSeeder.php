<?php

namespace App\Modules\Organization\Database\Seeders;

use App\Modules\Organization\Models\Location;
use Illuminate\Database\Seeder;

/**
 * Ports the default location tree from the legacy 0001_foundation.sql seed.
 */
class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            ['factory', 'المصنع', 'factory', null, 'إسكندرية', 1],
            ['main', 'المخزن الرئيسي', 'warehouse', 'factory', 'إسكندرية', 2],
            ['roushdy', 'رشدي', 'dark', null, 'إسكندرية', 10],
            ['agamy', 'العجمي', 'dark', null, 'إسكندرية', 11],
            ['masr-gedida', 'مصر الجديدة', 'dark', null, 'القاهرة', 12],
            ['maadi', 'المعادي', 'dark', null, 'القاهرة', 13],
            ['donate', 'مخزن التبرعات', 'donation', 'factory', 'إسكندرية', 90],
        ];

        foreach ($locations as [$id, $name, $type, $parent, $city, $sort]) {
            Location::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'type' => $type, 'parent_id' => $parent, 'city' => $city, 'sort' => $sort, 'active' => true],
            );
        }
    }
}