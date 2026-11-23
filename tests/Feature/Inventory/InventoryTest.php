<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
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

    public function test_owner_can_adjust_stock(): void
    {
        $item = Item::create(['name' => 'صنف مخزون', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);

        $this->actingAs($this->owner())->post('/stock/adjust', [
            'item_id' => $item->id,
            'location_id' => 'main',
            'qty' => '-5',
            'note' => 'تالف',
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_moves', [
            'item_id' => $item->id,
            'location_id' => 'main',
            'kind' => 'adjust',
            'qty' => -5,
        ]);
    }

    public function test_balance_is_derived_from_moves(): void
    {
        $item = Item::create(['name' => 'صنف رصيد', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);

        $this->actingAs($this->owner())->post('/stock/adjust', [
            'item_id' => $item->id, 'location_id' => 'main', 'qty' => '10',
        ]);
        $this->actingAs($this->owner())->post('/stock/adjust', [
            'item_id' => $item->id, 'location_id' => 'main', 'qty' => '-3',
        ]);

        $this->actingAs($this->owner())->get('/stock')->assertOk()->assertSee('7', false);
    }

    public function test_user_without_stock_adjust_cannot_adjust(): void
    {
        $user = User::create([
            'name' => 'موظف', 'phone' => '01888888888', 'password' => 'password', 'active' => true,
        ]);

        $this->actingAs($user)->post('/stock/adjust', [
            'item_id' => 1, 'location_id' => 'main', 'qty' => '1',
        ])->assertForbidden();
    }
}