<?php

namespace Tests\Feature\Organization;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use App\Modules\Organization\Models\Location;
use App\Modules\Organization\Models\LocationUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationUserTest extends TestCase
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

    public function test_can_assign_user_to_location(): void
    {
        $owner = $this->owner();
        $location = Location::find('roushdy');

        $this->actingAs($owner)
            ->post("/locations/{$location->id}/users", [
                'user_id' => $owner->id,
                'role' => 'manager',
                'is_primary' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('location_user', [
            'location_id' => 'roushdy',
            'user_id' => $owner->id,
            'role' => 'manager',
            'is_primary' => true,
        ]);
    }

    public function test_can_remove_user_from_location(): void
    {
        $owner = $this->owner();
        $location = Location::find('roushdy');

        // Assign user
        LocationUser::create([
            'location_id' => 'roushdy',
            'user_id' => $owner->id,
            'role' => 'cashier',
        ]);

        // Remove user
        $assignment = LocationUser::where('location_id', 'roushdy')
            ->where('user_id', $owner->id)
            ->first();

        $this->actingAs($owner)
            ->delete("/locations/{$location->id}/users/{$assignment->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('location_user', [
            'location_id' => 'roushdy',
            'user_id' => $owner->id,
        ]);
    }

    public function test_can_view_location_users(): void
    {
        $owner = $this->owner();
        $location = Location::find('roushdy');

        LocationUser::create([
            'location_id' => 'roushdy',
            'user_id' => $owner->id,
            'role' => 'manager',
            'is_primary' => true,
        ]);

        $this->actingAs($owner)
            ->get("/locations/{$location->id}/users")
            ->assertOk()
            ->assertSee('مستخدمو الفرع');
    }
}