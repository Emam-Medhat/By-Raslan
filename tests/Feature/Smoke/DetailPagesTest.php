<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Transfers\Models\Transfer;
use App\Modules\Trips\Models\Trip;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\Supplier;
use App\Modules\Production\Models\ItemRoute;
use App\Modules\Production\Models\ProdOrder;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Creates one record per parameterized detail route and asserts the page
 * renders (200), catching broken show pages / model bindings.
 */
class DetailPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    private function owner(): User
    {
        return User::where('is_owner', true)->firstOrFail();
    }

    public function test_detail_pages_render(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);

        $item = Item::create(['name' => 'صنف تفاصيل', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'app', 'price' => 10]);
        $box = Box::create(['item_id' => $item->id, 'weight' => 5, 'location_id' => 'roushdy', 'status' => 'stock']);

        // Orders detail
        $this->postJson('/api/orders', [
            'phone' => '01112223333', 'name' => 'عميل', 'address' => 'عنوان',
            'location_id' => 'roushdy', 'lines' => [['item_id' => $item->id, 'qty' => 40]],
        ])->assertOk();
        $order = \App\Modules\Orders\Models\OnlineOrder::firstOrFail();
        $this->get("/orders/{$order->id}")->assertOk();

        // Transfer detail (needs a box at factory)
        $transfer = Transfer::first();
        if (! $transfer) {
            $factoryBox = Box::create(['item_id' => $item->id, 'weight' => 5, 'location_id' => 'factory', 'status' => 'stock']);
            $this->post('/transfers', ['from_loc' => 'factory', 'to_loc' => 'main', 'boxes' => (string) $factoryBox->sn]);
            $transfer = Transfer::firstOrFail();
        }
        $this->get("/transfers/{$transfer->id}")->assertOk();

        // Trip detail
        $trip = Trip::create(['no' => 1, 'truck' => 't', 'status' => 'loading']);
        $this->get("/trips/{$trip->id}")->assertOk();

        // Production order detail
        ItemRoute::create(['item_id' => $item->id, 'ver' => 1, 'step' => 1, 'section_id' => 'f-wrap']);
        $prodOrder = ProdOrder::create(['no' => 1, 'item_id' => $item->id, 'qty' => 10, 'due' => now()->toDateString(), 'status' => 'planned']);
        $this->get("/production/{$prodOrder->id}")->assertOk();

        // Purchase order detail
        $supplier = Supplier::create(['name' => 'مورد', 'active' => true]);
        $po = PurchaseOrder::create(['no' => 1, 'supplier_id' => $supplier->id, 'status' => 'open']);
        $this->get("/purchasing/{$po->id}")->assertOk();
    }
}