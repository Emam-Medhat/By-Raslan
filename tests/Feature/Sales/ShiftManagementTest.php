<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Identity\Services\PermissionService;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Sales\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    private function cashier(string $phone = '01999999999'): User
    {
        $owner = User::where('is_owner', true)->firstOrFail();
        $clerk = User::create(['name' => 'كاشير', 'phone' => $phone, 'password' => 'password', 'active' => true]);
        app(PermissionService::class)->grant($owner, $clerk, 'pos.sell');

        return $clerk->refresh();
    }

    public function test_login_auto_opens_shift_for_cashier(): void
    {
        $cashier = $this->cashier();

        $this->post('/login', ['login' => $cashier->phone, 'password' => 'password'])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('shifts', ['cashier' => $cashier->id, 'status' => 'open']);
    }

    public function test_login_does_not_duplicate_open_shift(): void
    {
        $cashier = $this->cashier();

        $this->post('/login', ['login' => $cashier->phone, 'password' => 'password']);
        $this->post('/logout');
        $this->post('/login', ['login' => $cashier->phone, 'password' => 'password']);

        $this->assertSame(1, Shift::where('cashier', $cashier->id)->where('status', 'open')->count());
    }

    public function test_close_shift_closes_and_logs_out(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => 100]);
        $shift = Shift::where('cashier', $cashier->id)->firstOrFail();

        $this->actingAs($cashier)
            ->post('/pos/close', ['shift_id' => $shift->id, 'counted' => 100, 'notes' => 'مطابق'])
            ->assertRedirect(route('login'));

        $this->assertSame('closed', $shift->fresh()->status);
        $this->assertNotNull($shift->fresh()->logged_out_at);
        $this->assertSame('مطابق', $shift->fresh()->close_notes);
        $this->assertGuest();
    }

    public function test_cashier_without_shift_cannot_sell(): void
    {
        $cashier = $this->cashier();

        $this->actingAs($cashier)->postJson('/pos/sale', [
            'shift_id' => 1, 'lines' => [], 'discount' => 0, 'cash' => 0, 'card' => 0, 'idempotency_key' => (string) Str::uuid(),
        ])->assertForbidden();
    }

    public function test_shift_movement_report_lists_sales(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();
        $item = Item::create(['name' => 'صنف شيفت', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        \App\Modules\Inventory\Models\StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $this->actingAs($owner)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => 0]);
        $shift = Shift::where('cashier', $owner->id)->firstOrFail();

        $this->actingAs($owner)->postJson('/pos/sale', [
            'shift_id' => $shift->id,
            'lines' => [['item_id' => $item->id, 'qty' => 2]],
            'discount' => 0, 'cash' => 50, 'card' => 0,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertOk();

        $this->actingAs($owner)
            ->get("/shifts/{$shift->id}/movements")
            ->assertOk()
            ->assertSee('كشف حركة الشيفت');
    }
}