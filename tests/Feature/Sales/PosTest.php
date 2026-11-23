<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosTest extends TestCase
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

    private function openShift(User $owner): Shift
    {
        $this->actingAs($owner)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => 100])->assertSessionHasNoErrors();

        return Shift::where('cashier', $owner->id)->where('status', 'open')->firstOrFail();
    }

    public function test_item_sale_computes_totals_and_deducts_stock(): void
    {
        $item = Item::create(['name' => 'صنف بيع', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $owner = $this->owner();
        $shift = $this->openShift($owner);

        $this->actingAs($owner)->postJson('/pos/sale', [
            'shift_id' => $shift->id,
            'lines' => [['item_id' => $item->id, 'qty' => 2]],
            'discount' => 0, 'cash' => 50, 'card' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk()->assertJsonPath('sale.total', '20.00');

        $this->assertDatabaseHas('sales', ['total' => 20, 'change' => 30]);
        $this->assertSame(8.0, (float) StockMove::where('item_id', $item->id)->where('location_id', 'roushdy')->sum('qty'));
    }

    public function test_box_sale_marks_box_sold(): void
    {
        $item = Item::create(['name' => 'صنف علبة', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        $box = Box::create(['item_id' => $item->id, 'weight' => 5, 'location_id' => 'roushdy', 'status' => 'stock']);

        $owner = $this->owner();
        $shift = $this->openShift($owner);

        $this->actingAs($owner)->postJson('/pos/sale', [
            'shift_id' => $shift->id,
            'lines' => [['box_sn' => $box->sn]],
            'discount' => 0, 'cash' => 100, 'card' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        $this->assertSame('sold', $box->fresh()->status);
    }

    public function test_sale_is_idempotent(): void
    {
        $item = Item::create(['name' => 'صنف', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 5]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $owner = $this->owner();
        $shift = $this->openShift($owner);
        $key = (string) Str::uuid();

        $payload = [
            'shift_id' => $shift->id,
            'lines' => [['item_id' => $item->id, 'qty' => 1]],
            'discount' => 0, 'cash' => 10, 'card' => 0,
            'idempotency_key' => $key,
        ];

        $first = $this->actingAs($owner)->postJson('/pos/sale', $payload)->assertOk()->json('sale.no');
        $second = $this->actingAs($owner)->postJson('/pos/sale', $payload)->assertOk()->json('sale.no');

        $this->assertSame($first, $second);
        $this->assertSame(1, Sale::count());
    }

    public function test_close_shift_reports_difference(): void
    {
        $item = Item::create(['name' => 'صنف قفل', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $owner = $this->owner();
        $shift = $this->openShift($owner);

        $this->actingAs($owner)->postJson('/pos/sale', [
            'shift_id' => $shift->id,
            'lines' => [['item_id' => $item->id, 'qty' => 2]],
            'discount' => 0, 'cash' => 50, 'card' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        // expected = 100 opening + (50 cash - 30 change) = 120
        $this->actingAs($owner)->post('/pos/close', ['shift_id' => $shift->id, 'counted' => 125])
            ->assertSessionHasNoErrors();

        $shift->refresh();
        $this->assertSame('closed', $shift->status);
        $this->assertEqualsWithDelta(5.0, (float) $shift->counted_cash - (float) $shift->expected_cash, 0.001);
    }
}