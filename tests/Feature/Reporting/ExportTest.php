<?php

namespace Tests\Feature\Reporting;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_sales_export_downloads_csv(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();

        $response = $this->actingAs($owner)->get('/sales/export');
        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_stock_export_downloads_csv(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();

        $this->actingAs($owner)->get('/stock/export')->assertOk();
    }
}