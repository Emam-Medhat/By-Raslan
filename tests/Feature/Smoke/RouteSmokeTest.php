<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use App\Modules\Identity\Database\Seeders\IdentitySeeder;
use App\Modules\Organization\Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Opens every parameterless GET route as the owner and asserts it does not
 * 500/404. Catches missing views, undefined variables, and broken route names.
 */
class RouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(IdentitySeeder::class);
        $this->seed(OrganizationSeeder::class);
    }

    public function test_all_parameterless_get_routes_render(): void
    {
        $owner = User::where('is_owner', true)->firstOrFail();

        $skip = ['/', 'up', 'login'];

        $routes = collect(Route::getRoutes())
            ->filter(fn ($r) => in_array('GET', $r->methods(), true))
            ->filter(fn ($r) => ! str_contains($r->uri(), '{'))
            ->filter(fn ($r) => ! str_starts_with($r->uri(), 'api/'))
            ->filter(fn ($r) => ! str_starts_with($r->uri(), 'storage'))
            ->filter(fn ($r) => ! str_starts_with($r->uri(), '_'))
            ->filter(fn ($r) => ! in_array($r->uri(), $skip, true));

        $failures = [];

        foreach ($routes as $route) {
            $uri = '/'.ltrim($route->uri(), '/');
            $response = $this->actingAs($owner)->get($uri);
            $status = $response->getStatusCode();

            if ($status >= 500 || $status === 404) {
                $failures[] = "{$status}  {$uri}";
            }
        }

        $this->assertEmpty($failures, "Broken routes:\n".implode("\n", $failures));
    }
}