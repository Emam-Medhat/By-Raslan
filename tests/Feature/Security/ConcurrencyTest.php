<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Accounting\Models\ExpenseCategory;
use App\Modules\Catalog\Models\Item;
use App\Modules\Catalog\Models\Price;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Identity\Services\PermissionService;
use App\Modules\Inventory\Models\StockMove;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
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

    public function test_duplicate_expense_approval_is_rejected(): void
    {
        $owner = $this->owner();
        $category = ExpenseCategory::firstOrFail();

        $clerk = User::create(['name' => 'موظف', 'phone' => '01777777001', 'password' => 'password', 'active' => true]);
        app(PermissionService::class)->grant($owner, $clerk, 'acc.post', null, 50);

        $this->actingAs($clerk)->post('/accounts/expenses', [
            'location_id' => 'main', 'category_id' => $category->id, 'amount' => 100, 'paid_from' => 'cash',
        ])->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->assertSame('pending', $expense->status);

        $this->actingAs($owner)->post("/accounts/expenses/{$expense->id}/decide", ['approve' => 1])->assertSessionHasNoErrors();
        $this->assertSame('ok', $expense->fresh()->status);

        // Second approval must be rejected (no double decision).
        $this->actingAs($owner)->post("/accounts/expenses/{$expense->id}/decide", ['approve' => 1])
            ->assertSessionHasErrors('expense');
        $this->assertSame('ok', $expense->fresh()->status);
    }

    public function test_duplicate_sale_idempotency_key_creates_one_sale(): void
    {
        $item = Item::create(['name' => 'صنف تزامن', 'kind' => 'product', 'unit' => 'قطعة', 'active' => true]);
        Price::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'channel' => 'direct', 'price' => 10]);
        StockMove::create(['item_id' => $item->id, 'location_id' => 'roushdy', 'qty' => 10, 'kind' => 'receive']);

        $owner = $this->owner();
        $this->actingAs($owner)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => 0]);
        $shift = Shift::where('cashier', $owner->id)->firstOrFail();

        $key = (string) Str::uuid();
        $payload = [
            'shift_id' => $shift->id,
            'lines' => [['item_id' => $item->id, 'qty' => 2]],
            'discount' => 0, 'cash' => 50, 'card' => 0, 'idempotency_key' => $key,
        ];

        $this->actingAs($owner)->postJson('/pos/sale', $payload)->assertOk();
        $this->actingAs($owner)->postJson('/pos/sale', $payload)->assertOk();

        $this->assertSame(1, Sale::count());
        $this->assertSame(8.0, (float) StockMove::where('item_id', $item->id)->where('location_id', 'roushdy')->sum('qty'));
    }

    public function test_sequential_stock_adjust_accumulates_in_ledger(): void
    {
        $item = Item::create(['name' => 'صنف تسوية', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);
        $owner = $this->owner();

        $this->actingAs($owner)->post('/stock/adjust', ['item_id' => $item->id, 'location_id' => 'main', 'qty' => 10]);
        $this->actingAs($owner)->post('/stock/adjust', ['item_id' => $item->id, 'location_id' => 'main', 'qty' => -3]);

        $this->assertSame(7.0, (float) StockMove::where('item_id', $item->id)->where('location_id', 'main')->sum('qty'));
    }
}