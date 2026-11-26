<?php

namespace Tests\Feature\Workflow;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Organization\Models\Location;
use App\Modules\Production\Services\ProductionService;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Services\PosService;
use App\Modules\Orders\Services\ReservationService;
use App\Modules\Orders\Models\Customer;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Orders\Models\OnlineOrderBox;
use App\Modules\Orders\Models\OnlineOrderLine;
use App\Modules\Orders\Services\OrderCollectionService;
use App\Modules\Sales\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function seedBasics(): array
    {
        $loc = Location::create(['id'=>'shop1','name'=>'فرع 1','type'=>'shop','active'=>true,'sort'=>1]);
        Location::firstOrCreate(['id'=>'factory'], ['name'=>'المصنع','type'=>'factory','active'=>true,'sort'=>0]);
        $user = User::create(['name'=>'كاشير','phone'=>'01111111111','password'=>bcrypt('pass'),'active'=>true]);
        $item = Item::create(['name'=>'كبيبة','kind'=>'product','unit'=>'كجم','scale_plu'=>'000110','shelf_life_days'=>3,'active'=>true]);
        Price::create(['item_id'=>$item->id,'location_id'=>$loc->id,'channel'=>'direct','price'=>100]);
        return compact('loc','user','item');
    }

    public function test_scale_barcode_creates_unique_dish_in_branch(): void
    {
        $b = $this->seedBasics();
        $box = app(ProductionService::class)->packBox('00011000460', null, $b['user']->id, 'shop1');
        $this->assertNotNull($box->uid);
        $this->assertSame(12, strlen($box->uid));
        $this->assertSame('shop1', $box->location_id);
        $this->assertEquals(0.460, (float)$box->weight);
        $this->assertEquals('stock', $box->status);
        $this->assertNotNull($box->expires_at);
        $this->assertDatabaseHas('stock_moves', ['box_sn'=>$box->sn,'kind'=>'pack','location_id'=>'shop1']);
    }

    public function test_repeated_scale_barcode_still_creates_distinct_dishes(): void
    {
        $b = $this->seedBasics();
        $production = app(ProductionService::class);

        $first = $production->packBox('00011000460', null, $b['user']->id, 'shop1');
        $second = $production->packBox('00011000460', null, $b['user']->id, 'shop1');

        $this->assertNotSame($first->uid, $second->uid);
        $this->assertSame(2, \App\Modules\Inventory\Models\Box::count());
    }

    public function test_pos_blocks_dish_from_other_branch(): void
    {
        $b = $this->seedBasics();
        Location::create(['id'=>'shop2','name'=>'فرع 2','type'=>'shop','active'=>true,'sort'=>2]);
        $box = app(ProductionService::class)->packBox('00011000460', null, $b['user']->id, 'shop1');
        $shift = Shift::create(['location_id'=>'shop2','cashier'=>$b['user']->id,'open_cashier'=>$b['user']->id,'opened_at'=>now(),'opening_cash'=>0,'status'=>'open']);
        $this->expectException(\RuntimeException::class);
        app(PosService::class)->sale($shift->id, [['box_uid'=>$box->uid]], 0, 50, 0, (string)\Illuminate\Support\Str::uuid(), $b['user']->id, ['pay_method'=>'cash']);
    }

    public function test_reservation_converts_once(): void
    {
        $b = $this->seedBasics();
        $svc = app(ReservationService::class);
        $res = $svc->create([
            'location_id'=>'shop1','customer_name'=>'عميل','customer_phone'=>'0100','customer_address'=>'عنوان',
            'scheduled_for'=>now()->addDay()->toDateString(),'notes'=>null,
            'lines'=>[['item_id'=>$b['item']->id,'qty'=>2,'note'=>null]],
        ], $b['user']->id);
        $order = $svc->convertToOrder($res, $b['user']->id);
        $this->assertDatabaseHas('reservations', ['id'=>$res->id,'status'=>'converted','online_order_id'=>$order->id]);
        $this->assertSame($res->id, (int) $order->reservation_id);
        $this->expectException(\RuntimeException::class);
        $svc->convertToOrder($res->fresh(), $b['user']->id);
    }

    public function test_cash_collection_creates_invoice_and_links_the_delivered_dish(): void
    {
        $b = $this->seedBasics();
        $box = app(ProductionService::class)->packBox('00011000460', null, $b['user']->id, 'shop1');
        $customer = Customer::create(['phone'=>'01022222222','name'=>'عميل','address'=>'عنوان']);
        $order = OnlineOrder::create([
            'no'=>9001,'token'=>'order-token-1','customer_id'=>$customer->id,'location_id'=>'shop1',
            'channel'=>'phone','status'=>'out','delivery_fee'=>10,'est_total'=>46,'total'=>56,
        ]);
        $line = OnlineOrderLine::create([
            'order_id'=>$order->id,'line'=>1,'item_id'=>$b['item']->id,'qty'=>0.460,'unit_price'=>100,'prepared'=>0.460,
        ]);
        OnlineOrderBox::create([
            'order_id'=>$order->id,'box_sn'=>$box->sn,'line'=>$line->line,'qty'=>0.460,'active_box'=>$box->sn,
        ]);
        $shift = Shift::create([
            'location_id'=>'shop1','cashier'=>$b['user']->id,'open_cashier'=>$b['user']->id,
            'opened_at'=>now(),'opening_cash'=>200,'status'=>'open',
        ]);

        app(OrderCollectionService::class)->collect($order, 'cash', 60, $b['user']->id, true);

        $sale = \App\Modules\Sales\Models\Sale::where('online_order_id',$order->id)->firstOrFail();
        $this->assertDatabaseHas('sale_lines', ['sale_id'=>$sale->id,'box_sn'=>$box->sn,'item_id'=>$b['item']->id]);
        $this->assertSame('sold', $box->fresh()->status);
        $this->assertSame($sale->id, (int) $box->fresh()->sale_id);
        $this->assertEquals(4, (float) $sale->change);
        $this->assertEquals(256, app(ShiftService::class)->calculateExpectedCash($shift));
        $this->assertDatabaseHas('online_orders', [
            'id'=>$order->id,'paid'=>1,'collection_method'=>'cash','sale_id'=>$sale->id,
        ]);
    }

    public function test_electronic_collection_does_not_increase_cash_custody(): void
    {
        $b = $this->seedBasics();
        $customer = Customer::create(['phone'=>'01033333333','name'=>'عميل','address'=>'عنوان']);
        $order = OnlineOrder::create([
            'no'=>9002,'token'=>'order-token-2','customer_id'=>$customer->id,'location_id'=>'shop1',
            'channel'=>'phone','status'=>'out','delivery_fee'=>0,'est_total'=>100,'total'=>100,
        ]);
        OnlineOrderLine::create([
            'order_id'=>$order->id,'line'=>1,'item_id'=>$b['item']->id,'qty'=>1,'unit_price'=>100,'prepared'=>1,
        ]);
        StockMove::create(['item_id'=>$b['item']->id,'location_id'=>'shop1','qty'=>2,'kind'=>'receive']);
        $shift = Shift::create([
            'location_id'=>'shop1','cashier'=>$b['user']->id,'open_cashier'=>$b['user']->id,
            'opened_at'=>now(),'opening_cash'=>0,'status'=>'open',
        ]);

        app(OrderCollectionService::class)->collect($order, 'instapay', 100, $b['user']->id, true);

        $this->assertEquals(0, app(ShiftService::class)->calculateExpectedCash($shift));
        $this->assertDatabaseHas('sales', [
            'online_order_id'=>$order->id,'pay_method'=>'instapay','paid_cash'=>0,'paid_card'=>100,
        ]);
    }

    public function test_new_shift_automatically_carries_the_previous_custody(): void
    {
        $b = $this->seedBasics();
        Shift::create([
            'location_id'=>'shop1','cashier'=>$b['user']->id,'opened_at'=>now()->subDay(),
            'opening_cash'=>2000,'expected_cash'=>2000,'carryover_to_next_shift'=>2000,
            'closed_at'=>now()->subHour(),'status'=>'closed',
        ]);

        $next = app(ShiftService::class)->create([
            'location_id'=>'shop1','cashier'=>$b['user']->id,'opening_cash'=>0,
        ]);

        $this->assertEquals(2000, (float) $next->opening_cash);
    }
}
