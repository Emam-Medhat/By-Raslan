<?php

namespace Tests\Feature\Production;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Production\Models\ProdOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTest extends TestCase
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

    public function test_route_and_order_create_steps(): void
    {
        $item = Item::create(['name' => 'منتج', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);

        $this->actingAs($this->owner())->post("/production/items/{$item->id}/route", [
            'sections' => ['f-poultry', 'f-wrap'],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->owner())->post('/production', [
            'item_id' => $item->id, 'qty' => 100,
        ])->assertSessionHasNoErrors();

        $order = ProdOrder::firstOrFail();
        $this->assertSame(2, $order->steps()->count());
        $this->assertSame('planned', $order->status);
    }

    public function test_full_production_and_packing_flow(): void
    {
        $item = Item::create(['name' => 'منتج تغليف', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        $owner = $this->owner();

        $this->actingAs($owner)->post("/production/items/{$item->id}/route", ['sections' => ['f-poultry', 'f-wrap']]);
        $this->actingAs($owner)->post('/production', ['item_id' => $item->id, 'qty' => 100]);
        $order = ProdOrder::firstOrFail();

        $this->actingAs($owner)->post("/production/{$order->id}/steps/1/start", ['in_weight' => 100])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/production/{$order->id}/steps/1/finish", ['out_weight' => 90, 'waste' => 10])->assertSessionHasNoErrors();

        $this->assertSame('running', $order->fresh()->status);

        $this->actingAs($owner)->post('/pack', ['code' => $item->id.'|12.5', 'order_id' => $order->id])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('boxes', ['item_id' => $item->id, 'prod_order_id' => $order->id, 'weight' => 12.5]);

        $this->actingAs($owner)->post('/pack/finish', ['order_id' => $order->id])->assertSessionHasNoErrors();

        $this->assertSame('done', $order->fresh()->status);
        $this->assertGreaterThan(0, Box::where('prod_order_id', $order->id)->count());
    }
}