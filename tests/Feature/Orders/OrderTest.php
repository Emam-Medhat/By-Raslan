<?php

namespace Tests\Feature\Orders;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
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

    public function test_public_place_order_and_deliver(): void
    {
        $item = Item::create(['name' => 'صنف أونلاين', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'app', 'price' => 10]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        $box = Box::create(['item_id' => $item->id, 'weight' => 5, 'location_id' => 'roushdy', 'status' => 'stock']);

        $place = $this->postJson('/api/orders', [
            'phone' => '01234567890',
            'name' => 'عميل',
            'address' => 'عنوان',
            'location_id' => 'roushdy',
            'lines' => [['item_id' => $item->id, 'qty' => 40]],
        ])->assertOk()->assertJsonPath('ok', true);

        $order = OnlineOrder::firstOrFail();
        $this->assertSame('new', $order->status);

        $owner = $this->owner();
        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'accepted'])->assertSessionHasNoErrors();

        $this->actingAs($owner)->post("/orders/{$order->id}/scan", ['code' => (string) $box->sn])->assertSessionHasNoErrors();

        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'ready'])->assertSessionHasNoErrors();
        $this->actingAs($owner)->post("/orders/{$order->id}/status", ['status' => 'out', 'driver' => 'طيار'])->assertSessionHasNoErrors();

        $this->actingAs($owner)->post("/orders/{$order->id}/deliver")->assertSessionHasNoErrors();

        $this->assertSame('delivered', $order->fresh()->status);
        $this->assertSame('sold', $box->fresh()->status);
    }

    public function test_track_order_by_token(): void
    {
        $item = Item::create(['name' => 'صنف تتبع', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'app', 'price' => 10]);

        $place = $this->postJson('/api/orders', [
            'phone' => '01234567890', 'name' => 'عميل', 'address' => 'عنوان',
            'location_id' => 'roushdy', 'lines' => [['item_id' => $item->id, 'qty' => 40]],
        ])->assertOk();

        $token = $place->json('order.token');

        $this->getJson("/api/track/{$token}")->assertOk()->assertJsonPath('status', 'new');
    }
}