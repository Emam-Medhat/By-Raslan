<?php

namespace Tests\Feature\Accounting;

use App\Models\User;
use App\Modules\Accounting\Models\Expense;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Identity\Services\PermissionService;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
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

    public function test_owner_can_add_expense(): void
    {
        $category = \App\Modules\Accounting\Models\ExpenseCategory::firstOrFail();

        $this->actingAs($this->owner())->post('/accounts/expenses', [
            'location_id' => 'main',
            'category_id' => $category->id,
            'amount' => 100,
            'paid_from' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('expenses', ['amount' => 100, 'status' => 'ok']);
    }

    public function test_expense_above_limit_is_pending_and_can_be_approved(): void
    {
        $category = \App\Modules\Accounting\Models\ExpenseCategory::firstOrFail();

        $clerk = User::create([
            'name' => 'موظف حسابات',
            'phone' => '01999999999',
            'password' => 'password',
            'active' => true,
        ]);

        app(PermissionService::class)->grant($this->owner(), $clerk, 'acc.post', null, 50);

        $this->actingAs($clerk)->post('/accounts/expenses', [
            'location_id' => 'main',
            'category_id' => $category->id,
            'amount' => 100,
            'paid_from' => 'cash',
        ])->assertSessionHasNoErrors();

        $expense = Expense::firstOrFail();
        $this->assertSame('pending', $expense->status);

        $this->actingAs($this->owner())->post("/accounts/expenses/{$expense->id}/decide", ['approve' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame('ok', $expense->fresh()->status);
    }
}