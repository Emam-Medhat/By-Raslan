<?php

namespace Tests\Feature\Reporting;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_cross_cutting_pages_load(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();

        foreach (['/dashboard', '/statistics', '/settings', '/overrides', '/audit', '/sales', '/shifts'] as $path) {
            $this->actingAs($owner)->get($path)->assertOk();
        }
    }
}