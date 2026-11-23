<?php

namespace Tests\Feature\Organization;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_owner_can_view_locations(): void
    {
        $owner = User::where('is_owner', true)->first();

        $this->actingAs($owner)
            ->get('/locations')
            ->assertOk()
            ->assertSee('المصنع');
    }

    public function test_owner_can_create_location(): void
    {
        $owner = User::where('is_owner', true)->first();

        $this->actingAs($owner)->post('/locations', [
            'id' => 'test-branch',
            'name' => 'فرع تجريبي',
            'type' => 'shop',
            'city' => 'القاهرة',
        ])->assertRedirect(route('locations.index'));

        $this->assertDatabaseHas('locations', ['id' => 'test-branch', 'type' => 'shop']);
    }

    public function test_user_without_stock_edit_cannot_create_location(): void
    {
        $user = User::create([
            'name' => 'موظف',
            'phone' => '01666666666',
            'password' => 'password',
            'active' => true,
        ]);

        $this->actingAs($user)->post('/locations', [
            'id' => 'blocked',
            'name' => 'بلوك',
            'type' => 'shop',
        ])->assertForbidden();
    }
}