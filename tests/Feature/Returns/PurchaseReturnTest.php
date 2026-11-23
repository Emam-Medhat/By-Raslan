<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Returns\Models\PurchaseReturn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_purchase_return_deducts_stock(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();
        $item = Item::create(['name' => 'صنف مرتجع مورد', 'kind' => 'raw', 'unit' => 'كجم', 'active' => true]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'main', 'qty' => 10, 'kind' => 'receive']);

        $this->actingAs($owner)->post('/purchasing/suppliers', ['name' => 'مورد مرتجع'])->assertSessionHasNoErrors();
        $supplierId = \App\Modules\Purchasing\Models\Supplier::firstOrFail()->id;

        $this->actingAs($owner)->post('/purchase-returns', [
            'supplier_id' => $supplierId,
            'lines' => [['item_id' => $item->id, 'qty' => 3, 'cost' => 5]],
            'reason' => 'عيب',
        ])->assertSessionHasNoErrors();

        $return = PurchaseReturn::firstOrFail();
        $this->assertSame('pending', $return->status);

        $this->actingAs($owner)->post("/purchase-returns/{$return->id}/approve")->assertSessionHasNoErrors();

        $this->assertSame('approved', $return->fresh()->status);
        $this->assertSame(7.0, (float) StockMove::where('item_id', $item->id)->where('location_id', 'main')->sum('qty'));
    }
}