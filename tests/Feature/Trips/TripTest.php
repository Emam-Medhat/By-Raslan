<?php

namespace Tests\Feature\Trips;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\Box;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripTest extends TestCase
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

    public function test_full_loading_flow(): void
    {
        $item = Item::create(['name' => 'صنف رحلة', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        $box = Box::create(['item_id' => $item->id, 'weight' => 12, 'location_id' => 'factory', 'status' => 'stock']);

        $this->actingAs($this->owner())->post('/trips', ['truck' => 'دفع-4'])->assertRedirect();
        $trip = \App\Modules\Trips\Models\Trip::firstOrFail();
        $this->assertSame('loading', $trip->status);

        $this->actingAs($this->owner())->post("/trips/{$trip->id}/crates", [
            'crate_id' => 'CR-001', 'to_loc' => 'roushdy',
        ])->assertRedirect();

        $this->actingAs($this->owner())->post("/trips/{$trip->id}/load", [
            'crate_id' => 'CR-001', 'box_sn' => $box->sn,
        ])->assertSessionHasNoErrors();

        $this->assertSame('loaded', $box->fresh()->status);

        $this->actingAs($this->owner())->post("/trips/{$trip->id}/depart")->assertRedirect();

        $this->assertSame('out', $trip->fresh()->status);
        $this->assertSame('transit', $box->fresh()->status);
        $this->assertDatabaseHas('transfers', ['from_loc' => 'factory', 'to_loc' => 'roushdy', 'trip_id' => $trip->id]);
    }

    public function test_cannot_load_box_from_wrong_location(): void
    {
        $item = Item::create(['name' => 'صنف', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        $box = Box::create(['item_id' => $item->id, 'weight' => 5, 'location_id' => 'main', 'status' => 'stock']);

        $trip = \App\Modules\Trips\Models\Trip::create(['no' => 1, 'truck' => 't', 'status' => 'loading']);

        $this->actingAs($this->owner())->post("/trips/{$trip->id}/crates", ['crate_id' => 'CR-002', 'to_loc' => 'maadi']);
        $this->actingAs($this->owner())->post("/trips/{$trip->id}/load", [
            'crate_id' => 'CR-002', 'box_sn' => $box->sn,
        ])->assertSessionHasErrors('box');
    }
}