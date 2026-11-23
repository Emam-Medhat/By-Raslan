<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
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

    public function test_owner_can_create_item_with_barcodes(): void
    {
        $this->actingAs($this->owner())->post('/items', [
            'name' => 'زيت 1 لتر',
            'kind' => 'product',
            'unit' => 'كرتونة',
            'barcodes' => '1000000001, 1000000002',
            'active' => true,
        ])->assertRedirect(route('items.index'));

        $item = Item::where('name', 'زيت 1 لتر')->firstOrFail();
        $this->assertSame(2, $item->barcodes()->count());
    }

    public function test_user_without_items_edit_cannot_create_item(): void
    {
        $user = User::create([
            'name' => 'موظف',
            'phone' => '01777777777',
            'password' => 'password',
            'active' => true,
        ]);

        $this->actingAs($user)->post('/items', [
            'name' => 'صنف',
            'kind' => 'product',
            'unit' => 'كرتونة',
        ])->assertForbidden();
    }

    public function test_price_save_creates_price_and_log(): void
    {
        $item = Item::create(['name' => 'صنف سعر', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);

        $this->actingAs($this->owner())->post('/prices', [
            'item_id' => $item->id,
            'location_id' => 'main',
            'channel' => 'direct',
            'price' => '25.50',
        ])->assertRedirect();

        $this->assertDatabaseHas('prices', ['item_id' => $item->id, 'location_id' => 'main', 'price' => 25.50]);
        $this->assertDatabaseHas('price_log', ['item_id' => $item->id, 'new_price' => 25.50]);
    }
}