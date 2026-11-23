<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use App\Modules\Catalog\Models\Item;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Models\Location;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditPagesTest extends TestCase
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

    public function test_item_edit_renders_and_updates(): void
    {
        $owner = $this->owner();
        $item = Item::create(['name' => 'صنف', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true]);

        $this->actingAs($owner)->get("/items/{$item->id}/edit")->assertOk()->assertSee('تعديل الصنف');

        $this->actingAs($owner)->put("/items/{$item->id}", [
            'name' => 'صنف معدّل', 'kind' => 'product', 'unit' => 'كرتونة', 'active' => true,
        ])->assertRedirect(route('items.index'));

        $this->assertSame('صنف معدّل', $item->fresh()->name);
    }

    public function test_user_edit_renders_and_updates(): void
    {
        $owner = $this->owner();
        $user = User::create(['name' => 'موظف', 'phone' => '01999990000', 'password' => 'password', 'active' => true]);

        $this->actingAs($owner)->get("/users/{$user->id}/edit")->assertOk();

        $this->actingAs($owner)->put("/users/{$user->id}", [
            'name' => 'موظف معدّل', 'phone' => '01999990000', 'active' => true,
        ])->assertRedirect(route('users.index'));

        $this->assertSame('موظف معدّل', $user->fresh()->name);
    }

    public function test_location_edit_renders_and_updates(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->get('/locations/roushdy/edit')->assertOk();

        $this->actingAs($owner)->put('/locations/roushdy', [
            'name' => 'رشدي فرع', 'type' => 'dark', 'city' => 'إسكندرية',
        ])->assertRedirect(route('locations.index'));

        $this->assertSame('رشدي فرع', Location::find('roushdy')->name);
    }
}