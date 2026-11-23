<?php

namespace Tests\Feature\Purchasing;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingTest extends TestCase
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

    public function test_receive_po_creates_stock_and_lot(): void
    {
        $item = Item::create(['name' => 'صنف شراء', 'kind' => 'raw', 'unit' => 'كجم', 'active' => true]);
        \App\Modules\Purchasing\Models\ItemSpecVersion::create(['item_id' => $item->id, 'version' => 1, 'snapshot' => ['checks' => [], 'certs' => []], 'approved_at' => now()]);

        $owner = $this->owner();
        $this->actingAs($owner)->post('/purchasing/suppliers', ['name' => 'مورد تجريبي'])->assertSessionHasNoErrors();
        $supplierId = \App\Modules\Purchasing\Models\Supplier::firstOrFail()->id;

        $this->actingAs($owner)->post('/purchasing', [
            'supplier_id' => $supplierId,
            'lines' => [['item_id' => $item->id, 'qty' => 10, 'price' => 5]],
        ])->assertSessionHasNoErrors();

        $po = PurchaseOrder::firstOrFail();
        $this->assertSame('open', $po->status);

        // Seed an approved spec so receiving needs no override.
        $this->actingAs($owner)->post("/purchasing/specs/{$item->id}", ['checks' => [], 'certs' => []])
            ->assertSessionHasNoErrors();

        $this->actingAs($owner)->post("/purchasing/{$po->id}/receive", [
            'invoice_no' => 'INV-1',
            'lines' => [1 => ['weighed' => 8, 'rejected' => 2, 'price' => 5]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(6.0, (float) StockMove::where('item_id', $item->id)->where('location_id', 'main')->sum('qty'));
        $this->assertDatabaseHas('lots', ['item_id' => $item->id, 'qty' => 6]);
        $this->assertSame('partial', $po->fresh()->status);
    }

    public function test_pay_supplier_records_payment(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->post('/purchasing/suppliers', ['name' => 'مورد دفع'])->assertSessionHasNoErrors();
        $supplier = \App\Modules\Purchasing\Models\Supplier::firstOrFail();

        $this->actingAs($owner)->post("/purchasing/suppliers/{$supplier->id}/pay", [
            'amount' => 50, 'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supplier_payments', ['supplier_id' => $supplier->id, 'amount' => 50]);
    }
}