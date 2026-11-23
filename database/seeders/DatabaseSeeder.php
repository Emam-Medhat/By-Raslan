<?php

namespace Database\Seeders;

use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IdentitySeeder::class,
            OrganizationSeeder::class,
        ]);
    }
}