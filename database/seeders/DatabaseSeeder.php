<?php

namespace Database\Seeders;

use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Catalog\Database\Seeders\CatalogSeeder;
use App\Modules\Inventory\Database\Seeders\InventorySeeder;
use App\Modules\Sales\Database\Seeders\SalesSeeder;
use App\Modules\Transfers\Database\Seeders\TransfersSeeder;
use App\Modules\Trips\Database\Seeders\TripsSeeder;
use App\Modules\Production\Database\Seeders\ProductionSeeder;
use App\Modules\Purchasing\Database\Seeders\PurchasingSeeder;
use App\Modules\Returns\Database\Seeders\ReturnsSeeder;
use App\Modules\Accounting\Database\Seeders\AccountingSeeder;
use App\Modules\Orders\Database\Seeders\DriversSeeder;
use App\Modules\Orders\Database\Seeders\OrdersSeeder;
use App\Modules\Notifications\Database\Seeders\NotificationsSeeder;
use App\Modules\Audit\Database\Seeders\AuditSeeder;
use App\Modules\Settings\Database\Seeders\SettingsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Foundation - must run first
            IdentitySeeder::class,      // Users, roles, permissions
            OrganizationSeeder::class,  // Locations
            
            // Core catalog & inventory
            CatalogSeeder::class,       // Families, items, prices, barcodes
            InventorySeeder::class,     // Boxes, stock moves, stock counts
            
            // Sales & POS (depends on catalog, inventory, locations, users)
            SalesSeeder::class,         // Shifts, sales, sale lines, cash transfers, shift expenses
            
            // Transfers (depends on inventory boxes, locations)
            TransfersSeeder::class,
            
            // Trips (depends on inventory boxes, crates, locations)
            TripsSeeder::class,
            
            // Production (depends on catalog items, locations)
            ProductionSeeder::class,
            
            // Purchasing (depends on suppliers, catalog items, locations)
            PurchasingSeeder::class,
            
            // Returns (depends on sales, purchases, locations)
            ReturnsSeeder::class,
            
            // Accounting (depends on locations, users)
            AccountingSeeder::class,
            
            // Orders (depends on customers, catalog items, locations)
            OrdersSeeder::class,
            DriversSeeder::class,
            
            // Notifications (depends on users, customers)
            NotificationsSeeder::class,
            
            // Audit (can run anytime after tables exist)
            AuditSeeder::class,
            
            // Settings (can run anytime)
            SettingsSeeder::class,
        ]);
    }
}