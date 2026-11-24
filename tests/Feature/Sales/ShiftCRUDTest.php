<?php

namespace Tests\Feature\Sales;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Sales\Models\CashTransfer;
use App\Modules\Sales\Models\Shift;
use App\Modules\Sales\Models\ShiftExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftCRUDTest extends TestCase
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

    public function test_can_view_shifts_index(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->get('/shifts')
            ->assertOk()
            ->assertSee('إدارة الشيفتات');
    }

    public function test_can_create_shift(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post('/shifts', [
                'location_id' => 'roushdy',
                'cashier' => $owner->id,
                'opening_cash' => 100,
            ])
            ->assertRedirect(route('shifts.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('shifts', [
            'location_id' => 'roushdy',
            'cashier' => $owner->id,
            'status' => 'open',
        ]);
    }

    public function test_cannot_create_duplicate_open_shift(): void
    {
        $owner = $this->owner();

        // Create first shift
        $this->actingAs($owner)
            ->post('/shifts', [
                'location_id' => 'roushdy',
                'cashier' => $owner->id,
                'opening_cash' => 100,
            ]);

        // Try to create second shift
        $this->actingAs($owner)
            ->post('/shifts', [
                'location_id' => 'roushdy',
                'cashier' => $owner->id,
                'opening_cash' => 200,
            ])
            ->assertSessionHasErrors('cashier');
    }

    public function test_can_view_shift_details(): void
    {
        $owner = $this->owner();

        $shift = Shift::create([
            'location_id' => 'roushdy',
            'cashier' => $owner->id,
            'open_cashier' => $owner->id,
            'opened_at' => now(),
            'opening_cash' => 100,
            'status' => 'open',
        ]);

        $this->actingAs($owner)
            ->get("/shifts/{$shift->id}")
            ->assertOk()
            ->assertSee('تفاصيل الشيفت')
            ->assertSee('رصيد البداية');
    }

    public function test_can_close_shift_with_carryover(): void
    {
        $owner = $this->owner();

        $shift = Shift::create([
            'location_id' => 'roushdy',
            'cashier' => $owner->id,
            'open_cashier' => $owner->id,
            'opened_at' => now(),
            'opening_cash' => 100,
            'status' => 'open',
        ]);

        $this->actingAs($owner)
            ->post("/shifts/{$shift->id}/close", [
                'counted_cash' => 150,
                'notes' => 'مطابق',
            ])
            ->assertRedirect(route('shifts.index'));

        $shift->refresh();
        $this->assertSame('closed', $shift->status);
        $this->assertEquals(150, (float) $shift->counted_cash);
        $this->assertEquals(150, (float) $shift->carryover_to_next_shift);

        // Check carryover transfer created
        $this->assertDatabaseHas('cash_transfers', [
            'from_shift_id' => $shift->id,
            'type' => 'to_next_shift',
            'amount' => 150,
        ]);
    }

    public function test_can_add_shift_expense(): void
    {
        $owner = $this->owner();

        $shift = Shift::create([
            'location_id' => 'roushdy',
            'cashier' => $owner->id,
            'open_cashier' => $owner->id,
            'opened_at' => now(),
            'opening_cash' => 100,
            'status' => 'open',
        ]);

        $this->actingAs($owner)
            ->post("/shifts/{$shift->id}/expenses", [
                'title' => 'مصروف تجريبي',
                'amount' => 50,
            ])
            ->assertRedirect(route('shifts.show', $shift));

        $this->assertDatabaseHas('shift_expenses', [
            'shift_id' => $shift->id,
            'title' => 'مصروف تجريبي',
            'amount' => 50,
            'status' => 'pending',
        ]);
    }

    public function test_can_add_cash_transfer(): void
    {
        $owner = $this->owner();

        $shift = Shift::create([
            'location_id' => 'roushdy',
            'cashier' => $owner->id,
            'open_cashier' => $owner->id,
            'opened_at' => now(),
            'opening_cash' => 100,
            'status' => 'open',
        ]);

        $this->actingAs($owner)
            ->post("/shifts/{$shift->id}/transfers", [
                'type' => 'to_manager',
                'amount' => 30,
            ])
            ->assertRedirect(route('shifts.show', $shift));

        $this->assertDatabaseHas('cash_transfers', [
            'from_shift_id' => $shift->id,
            'type' => 'to_manager',
            'amount' => 30,
        ]);
    }

    public function test_shift_expenses_reduce_expected_cash(): void
    {
        $owner = $this->owner();

        // Create item and price
        $item = Item::create(['name' => 'صنف', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        // Create shift with 100 opening
        $shift = Shift::create([
            'location_id' => 'roushdy',
            'cashier' => $owner->id,
            'open_cashier' => $owner->id,
            'opened_at' => now(),
            'opening_cash' => 100,
            'status' => 'open',
        ]);

        // Add sale: 2 items x 10 = 20 total, paid 50 cash, change 30
        // Cash sale = 50 - 30 = 20
        \App\Modules\Sales\Models\Sale::create([
            'no' => 100001,
            'location_id' => 'roushdy',
            'shift_id' => $shift->id,
            'cashier' => $owner->id,
            'at' => now(),
            'subtotal' => 20,
            'discount' => 0,
            'total' => 20,
            'paid_cash' => 50,
            'paid_card' => 0,
            'change' => 30,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        // Add shift expense: 10
        ShiftExpense::create([
            'shift_id' => $shift->id,
            'user_id' => $owner->id,
            'location_id' => 'roushdy',
            'title' => 'مصروف',
            'amount' => 10,
            'status' => 'approved',
        ]);

        // Expected = 100 (opening) + 20 (cash sales) - 10 (expenses) = 110
        $this->actingAs($owner)
            ->post("/shifts/{$shift->id}/close", [
                'counted_cash' => 110,
            ]);

        $shift->refresh();
        $this->assertEquals(110, (float) $shift->expected_cash);
    }
}