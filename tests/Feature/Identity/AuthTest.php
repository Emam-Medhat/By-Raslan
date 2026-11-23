<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
    }

    public function test_owner_can_login_with_phone_and_password(): void
    {
        $response = $this->post('/login', [
            'login' => '01000000000',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $response = $this->post('/login', [
            'login' => '01000000000',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_login(): void
    {
        User::create([
            'name' => 'موقوف',
            'phone' => '01111111111',
            'password' => 'password',
            'active' => false,
        ]);

        $response = $this->post('/login', [
            'login' => '01111111111',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $owner = User::where('is_owner', true)->first();

        $this->actingAs($owner)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}