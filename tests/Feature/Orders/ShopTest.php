<?php

namespace Tests\Feature\Orders;

use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_public_shop_page_loads(): void
    {
        $this->get('/shop')->assertOk()->assertSee('اطلب أونلاين');
    }

    public function test_track_page_loads_for_placed_order(): void
    {
        $item = Item::create(['name' => 'صنف متجر', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'app', 'price' => 10]);

        $place = $this->postJson('/api/orders', [
            'phone' => '01234567890', 'name' => 'عميل', 'address' => 'عنوان',
            'location_id' => 'roushdy', 'lines' => [['item_id' => $item->id, 'qty' => 40]],
        ])->assertOk();

        $token = $place->json('order.token');
        $this->get("/track/{$token}")->assertOk();
    }

    public function test_accounts_page_loads(): void
    {
        $owner = \App\Models\User::where('is_owner', true)->firstOrFail();
        $this->actingAs($owner)->get('/accounts')->assertOk();
    }

    public function test_crates_page_loads(): void
    {
        $owner = \App\Models\User::where('is_owner', true)->firstOrFail();
        $this->actingAs($owner)->get('/crates')->assertOk();
    }
}