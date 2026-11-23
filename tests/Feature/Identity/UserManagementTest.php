<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    private function owner(): User
    {
        return User::where('is_owner', true)->firstOrFail();
    }

    public function test_owner_can_view_users(): void
    {
        $this->actingAs($this->owner())
            ->get('/users')
            ->assertOk()
            ->assertSee('المستخدمون');
    }

    public function test_user_without_users_manage_is_forbidden(): void
    {
        $user = User::create([
            'name' => 'موظف',
            'phone' => '01222222222',
            'password' => 'password',
            'active' => true,
        ]);

        $this->actingAs($user)->get('/users')->assertForbidden();
    }

    public function test_owner_can_create_user(): void
    {
        $response = $this->actingAs($this->owner())->post('/users', [
            'name' => 'كاشير جديد',
            'phone' => '01333333333',
            'password' => 'password123',
            'active' => true,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['phone' => '01333333333', 'name' => 'كاشير جديد']);
    }

    public function test_owner_can_grant_permission(): void
    {
        $user = User::create([
            'name' => 'موظف',
            'phone' => '01444444444',
            'password' => 'password',
            'active' => true,
        ]);

        $this->actingAs($this->owner())
            ->post("/users/{$user->id}/grant", ['permission' => 'pos.sell'])
            ->assertRedirect(route('users.index'));

        $this->assertTrue($user->fresh()->hasPermission('pos.sell'));
    }

    public function test_owner_can_revoke_permission(): void
    {
        $user = User::create([
            'name' => 'موظف',
            'phone' => '01555555555',
            'password' => 'password',
            'active' => true,
        ]);

        $this->actingAs($this->owner())
            ->post("/users/{$user->id}/grant", ['permission' => 'stock.view']);

        $this->actingAs($this->owner())
            ->post("/users/{$user->id}/revoke", ['permission' => 'stock.view'])
            ->assertRedirect(route('users.index'));

        $this->assertFalse($user->fresh()->hasPermission('stock.view'));
    }
}