<?php

namespace Tests\Feature\Purchasing;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QaSpecTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_receiving_requires_all_spec_checks(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();
        $item = Item::create(['name' => 'صنف مواصفة', 'kind' => 'raw', 'unit' => 'كجم', 'active' => true]);

        $this->actingAs($owner)->post('/purchasing/suppliers', ['name' => 'مورد مواصفة'])->assertSessionHasNoErrors();
        $supplierId = \App\Modules\Purchasing\Models\Supplier::firstOrFail()->id;

        $this->actingAs($owner)->post('/purchasing', [
            'supplier_id' => $supplierId,
            'lines' => [['item_id' => $item->id, 'qty' => 10, 'price' => 5]],
        ])->assertSessionHasNoErrors();
        $po = PurchaseOrder::firstOrFail();

        // Save a spec version requiring 2 checks.
        $this->actingAs($owner)->post("/purchasing/specs/{$item->id}", [
            'checks' => ['اللون طبيعي', 'الحرارة مناسبة'],
        ])->assertSessionHasNoErrors();

        // Receive with missing checks -> rejected.
        $this->actingAs($owner)->post("/purchasing/{$po->id}/receive", [
            'lines' => [1 => ['weighed' => 8, 'rejected' => 0, 'price' => 5, 'checks' => [['t' => 'اللون', 'ok' => true]]]],
        ])->assertSessionHasErrors();

        // Receive with all checks -> accepted + receipt_checks recorded.
        $this->actingAs($owner)->post("/purchasing/{$po->id}/receive", [
            'lines' => [1 => ['weighed' => 8, 'rejected' => 0, 'price' => 5, 'checks' => [
                ['t' => 'اللون', 'ok' => true],
                ['t' => 'الحرارة', 'ok' => true],
            ]]],
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, \App\Modules\Purchasing\Models\ReceiptCheck::count());
    }
}