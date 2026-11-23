<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Accounting\Models\ExpenseCategory;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Identity\Services\PermissionService;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Sales\Models\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationMatrixTest extends TestCase
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

    private function userWith(string ...$perms): User
    {
        $user = User::create([
            'name' => 'موظف '.uniqid(),
            'phone' => '01'.random_int(100000000, 999999999),
            'password' => 'password',
            'active' => true,
        ]);

        $owner = $this->owner();
        foreach ($perms as $perm) {
            app(PermissionService::class)->grant($owner, $user, $perm);
        }

        return $user->refresh();
    }

    public function test_readonly_stock_user_cannot_adjust(): void
    {
        $user = $this->userWith('stock.view');

        $this->actingAs($user)->get('/stock')->assertOk();
        $this->actingAs($user)->post('/stock/adjust', [
            'item_id' => 1, 'location_id' => 'main', 'qty' => 1,
        ])->assertForbidden();
    }

    public function test_expense_poster_cannot_approve(): void
    {
        $user = $this->userWith('acc.post');
        $category = ExpenseCategory::firstOrFail();

        $this->actingAs($user)->post('/accounts/expenses', [
            'location_id' => 'main', 'category_id' => $category->id, 'amount' => 5, 'paid_from' => 'cash',
        ])->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->actingAs($user)->post("/accounts/expenses/{$expense->id}/decide", ['approve' => 1])->assertForbidden();
    }

    public function test_cashier_cannot_close_another_cashiers_shift(): void
    {
        $a = $this->userWith('pos.sell');
        $b = $this->userWith('pos.sell');

        $this->actingAs($a)->post('/pos/open', ['location_id' => 'roushdy', 'opening_cash' => 0])->assertSessionHasNoErrors();
        $shift = Shift::where('cashier', $a->id)->firstOrFail();

        $this->actingAs($b)->post('/pos/close', ['shift_id' => $shift->id, 'counted' => 0])
            ->assertSessionHasErrors('shift');
    }

    public function test_user_without_users_manage_forbidden(): void
    {
        $user = $this->userWith('stock.view');

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_inactive_user_is_blocked(): void
    {
        $user = $this->userWith('stock.view');
        $user->update(['active' => false]);

        $this->actingAs($user->refresh())->get('/dashboard')->assertForbidden();
    }
}