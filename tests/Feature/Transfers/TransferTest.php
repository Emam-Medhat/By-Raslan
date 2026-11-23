<?php

namespace Tests\Feature\Transfers;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
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

    private function stockBox(string $loc = 'factory'): Box
    {
        $item = Item::create(['name' => 'صنف تحويل', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);

        return Box::create([
            'item_id' => $item->id,
            'weight' => 10,
            'location_id' => $loc,
            'status' => 'stock',
        ]);
    }

    public function test_owner_can_send_and_receive_transfer(): void
    {
        $box = $this->stockBox('factory');

        $send = $this->actingAs($this->owner())->post('/transfers', [
            'from_loc' => 'factory',
            'to_loc' => 'main',
            'boxes' => (string) $box->sn,
        ]);
        $send->assertRedirect();

        $transfer = \App\Modules\Transfers\Models\Transfer::firstOrFail();
        $this->assertSame('transit', $transfer->status);
        $this->assertSame('transit', $box->fresh()->status);

        $this->actingAs($this->owner())->post("/transfers/{$transfer->id}/receive", [
            'boxes' => (string) $box->sn,
        ])->assertRedirect();

        $this->assertSame('received', $transfer->fresh()->status);
        $this->assertSame('stock', $box->fresh()->status);
        $this->assertSame('main', $box->fresh()->location_id);
    }

    public function test_receive_marks_unscanned_boxes_missing(): void
    {
        $box = $this->stockBox('factory');
        $box2 = $this->stockBox('factory');

        $this->actingAs($this->owner())->post('/transfers', [
            'from_loc' => 'factory',
            'to_loc' => 'main',
            'boxes' => $box->sn.','.$box2->sn,
        ]);

        $transfer = \App\Modules\Transfers\Models\Transfer::firstOrFail();

        $this->actingAs($this->owner())->post("/transfers/{$transfer->id}/receive", [
            'boxes' => (string) $box->sn, // only one scanned
        ])->assertRedirect();

        $this->assertSame('stock', $box->fresh()->status);
        $this->assertSame('missing', $box2->fresh()->status);
    }
}