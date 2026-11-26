<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Returns\Models\SalesReturn;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DailyClosingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    private function sellBox(User $owner, Item $item, float $weight = 5): array
    {
        $box = Box::create(['item_id' => $item->id, 'weight' => $weight, 'location_id' => 'roushdy', 'status' => 'stock']);

        $this->actingAs($owner)->postJson('/pos/sale', [
            'shift_id' => Shift::where('cashier', $owner->id)->firstOrFail()->id,
            'lines' => [['box_sn' => $box->sn]],
            'discount' => 0, 'cash' => 100, 'card' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        return [$box, Sale::orderByDesc('id')->firstOrFail()];
    }

    private function openShift(User $owner, float $cash = 0): Shift
    {
        $this->actingAs($owner)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => $cash])->assertSessionHasNoErrors();

        return Shift::where('cashier', $owner->id)->where('status', 'open')->firstOrFail();
    }

    public function test_return_one_item_to_waste_does_not_restock_and_refunds_cash(): void
    {
        $item = Item::create(['name' => 'صنف هالك', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        $owner = User::where('is_owner', true)->firstOrFail();
        $this->openShift($owner);

        [$box, $sale] = $this->sellBox($owner, $item);

        $this->actingAs($owner)->post('/returns', [
            'sale_id' => $sale->id,
            'destination' => 'waste',
            'refund_method' => 'cash',
            'reason' => 'بايظة',
        ])->assertSessionHasNoErrors();

        $return = SalesReturn::firstOrFail();
        $this->assertSame('waste', $return->destination);
        $this->assertSame(10.0, (float) $return->refund_amount);

        $this->actingAs($owner)->post("/returns/{$return->id}/approve")->assertSessionHasNoErrors();

        $this->assertSame('waste', $box->fresh()->status);
        // Sold tray returned as waste: no quantity comes back to stock.
        $this->assertSame(-5.0, (float) StockMove::where('item_id', $item->id)->where('location_id', 'roushdy')->sum('qty'));
        // Expected cash drops by the cash refund (10 cash in - 10 refunded).
        $shift = Shift::where('cashier', $owner->id)->firstOrFail();
        $this->assertSame(0.0, app(ShiftService::class)->calculateExpectedCash($shift));
    }

    public function test_return_whole_invoice_to_donations_moves_tray_to_donate_location(): void
    {
        $item = Item::create(['name' => 'صنف تبرع', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        $owner = User::where('is_owner', true)->firstOrFail();
        $this->openShift($owner);

        [$box, $sale] = $this->sellBox($owner, $item);

        $this->actingAs($owner)->post('/returns', [
            'sale_id' => $sale->id,
            'destination' => 'donated',
            'refund_method' => 'none',
        ])->assertSessionHasNoErrors();

        $return = SalesReturn::firstOrFail();
        $this->actingAs($owner)->post("/returns/{$return->id}/approve")->assertSessionHasNoErrors();

        $this->assertSame('donated', $box->fresh()->status);
        $this->assertSame('donate', $box->fresh()->location_id);
        $this->assertSame(0.0, (float) $return->fresh()->refund_amount);
        $this->assertDatabaseHas('stock_moves', ['kind' => 'donate', 'box_sn' => $box->sn, 'location_id' => 'donate']);
    }

    public function test_daily_closing_shows_sales_and_return_separately(): void
    {
        $item = Item::create(['name' => 'صنف يومية', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        $owner = User::where('is_owner', true)->firstOrFail();
        $this->openShift($owner);

        [, $sale] = $this->sellBox($owner, $item);

        $this->actingAs($owner)->post('/returns', ['sale_id' => $sale->id, 'destination' => 'stock'])->assertSessionHasNoErrors();
        $return = SalesReturn::firstOrFail();
        $this->actingAs($owner)->post("/returns/{$return->id}/approve")->assertSessionHasNoErrors();

        $response = $this->actingAs($owner)->get('/reports/daily-closing?date='.now()->toDateString().'&location_id=roushdy');
        $response->assertOk();
        // 1 order sold for 10, 1 returned for 10, net 0 — both written.
        $response->assertSee('10.00');
        $response->assertSee('الصافي بعد المرتجعات');
        $response->assertSee('المرتجعات (1)');
    }

    public function test_custody_carries_over_to_next_shift_once(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();
        $shift = $this->openShift($owner, 0);

        // Close with 500 counted: 500 becomes the carryover custody.
        $this->actingAs($owner)->post("/shifts/{$shift->id}/close", ['counted_cash' => 500])->assertSessionHasNoErrors();
        $this->assertSame(500.0, (float) $shift->fresh()->carryover_to_next_shift);
        $this->assertSame(500.0, app(ShiftService::class)->suggestedOpeningCash('roushdy'));

        // Open the next shift with exactly the carried amount: the
        // carryover transfer is linked to it and consumed once.
        $next = $this->openShift($owner, 500);
        $transfer = CashTransfer::where('from_shift_id', $shift->id)->where('type', 'to_next_shift')->firstOrFail();
        $this->assertSame($next->id, (int) $transfer->to_shift_id);
        $this->assertSame(0.0, app(ShiftService::class)->suggestedOpeningCash('roushdy'));
    }
}
