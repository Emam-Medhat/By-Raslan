<?php

namespace Tests\Feature\Inventory;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\StockCount;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountTest extends TestCase
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

    public function test_can_create_stock_count(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/stock-counts', [
                'location_id' => 'roushdy',
                'notes' => 'جرد تجريبي',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_counts', [
            'location_id' => 'roushdy',
            'status' => 'draft',
        ]);
    }

    public function test_can_add_item_to_stock_count(): void
    {
        $owner = $this->owner();

        // Create item with stock
        $item = Item::create(['name' => 'صنف جرد', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $count = StockCount::create([
            'location_id' => 'roushdy',
            'user_id' => $owner->id,
            'count_number' => 'SC-20260101-001',
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post("/stock-counts/{$count->id}/items", [
                'item_id' => $item->id,
                'actual_quantity' => 8,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('stock_count_items', [
            'stock_count_id' => $count->id,
            'item_id' => $item->id,
            'system_quantity' => 10,
            'actual_quantity' => 8,
            'difference' => -2,
        ]);
    }

    public function test_approve_stock_count_creates_adjustment(): void
    {
        $owner = $this->owner();

        $item = Item::create(['name' => 'صنف تعديل', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $count = StockCount::create([
            'location_id' => 'roushdy',
            'user_id' => $owner->id,
            'count_number' => 'SC-20260101-002',
            'status' => 'draft',
        ]);

        // Add item with difference
        \App\Modules\Inventory\Models\StockCountItem::create([
            'stock_count_id' => $count->id,
            'item_id' => $item->id,
            'system_quantity' => 10,
            'actual_quantity' => 7,
            'difference' => -3,
        ]);

        $this->actingAs($owner)
            ->post("/stock-counts/{$count->id}/approve")
            ->assertRedirect();

        $count->refresh();
        $this->assertSame('approved', $count->status);
        $this->assertNotNull($count->approved_at);

        // Check adjustment stock move created
        $this->assertDatabaseHas('stock_moves', [
            'item_id' => $item->id,
            'location_id' => 'roushdy',
            'qty' => -3,
            'kind' => 'adjust',
        ]);
    }

    public function test_can_view_stock_counts_index(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->get('/stock-counts')
            ->assertOk()
            ->assertSee('جرد المخزون');
    }
}