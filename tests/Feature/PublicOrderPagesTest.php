<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Orders\Models\OnlineOrder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicOrderPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_public_order_page_loads(): void
    {
        $this->get('/order')->assertOk()->assertSee('اطلب أونلاين');
    }

    public function test_public_tracking_page_loads(): void
    {
        $item = Item::create(['name' => 'صنف تتبع', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'app', 'price' => 10]);
        $res = $this->postJson('/api/orders', [
            'phone' => '01234567890', 'name' => 'عميل', 'address' => 'عنوان',
            'location_id' => 'roushdy', 'lines' => [['item_id' => $item->id, 'qty' => 40]],
        ])->assertOk();
        $token = $res->json('order.token');
        $this->get("/t/{$token}")->assertOk()->assertSee('طلب رقم');
    }
}
