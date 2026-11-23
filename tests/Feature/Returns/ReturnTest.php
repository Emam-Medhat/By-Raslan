<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Sales\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReturnTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_sale_return_restocks_box(): void
    {
        $item = Item::create(['name' => 'صنف مرتجع', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        $box = Box::create(['item_id' => $item->id, 'weight' => 5, 'location_id' => 'roushdy', 'status' => 'stock']);

        $owner = User::where('is_owner', true)->firstOrFail();
        $this->actingAs($owner)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => 0])->assertSessionHasNoErrors();
        $shift = \App\Modules\Sales\Models\Shift::where('cashier', $owner->id)->firstOrFail();

        $this->actingAs($owner)->postJson('/pos/sale', [
            'shift_id' => $shift->id,
            'lines' => [['box_sn' => $box->sn]],
            'discount' => 0, 'cash' => 100, 'card' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertSame('sold', $box->fresh()->status);
        $sale = Sale::firstOrFail();

        $this->actingAs($owner)->post('/returns', ['sale_id' => $sale->id, 'reason' => 'عميل رجّع'])->assertSessionHasNoErrors();
        $return = \App\Modules\Returns\Models\SalesReturn::firstOrFail();

        $this->actingAs($owner)->post("/returns/{$return->id}/approve")->assertSessionHasNoErrors();

        $this->assertSame('stock', $box->fresh()->status);
        $this->assertDatabaseHas('stock_moves', ['kind' => 'return', 'box_sn' => $box->sn]);
    }
}