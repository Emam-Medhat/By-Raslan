<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Auto-discovers domain modules under app/Modules and wires them into the
 * host application. Each module is a self-contained bounded context
 * (Models, Services, Http, Policies, Events, Jobs, routes, migrations).
 *
 * This is the seam that lets a module be extracted into a standalone
 * microservice later without touching the host.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach ($this->modulePaths() as $path) {
            $provider = $this->providerClassFor($path);
            if ($provider && class_exists($provider)) {
                $this->app->register($provider);
            }
        }
    }

    public function boot(): void
    {
        foreach ($this->modulePaths() as $path) {
            $routes = $path.'/routes/web.php';
            if (is_file($routes)) {
                Route::middleware('web')->group($routes);
            }
        }
    }

    /** @return list<string> */
    protected function modulePaths(): array
    {
        $base = base_path('app/Modules');

        if (! is_dir($base)) {
            return [];
        }

        return collect(glob($base.'/*', GLOB_ONLYDIR))
            ->filter(fn (string $dir) => is_dir($dir))
            ->values()
            ->all();
    }

    protected function providerClassFor(string $path): ?string
    {
        $name = basename($path);

        return "App\\Modules\\{$name}\\Providers\\{$name}ServiceProvider";
    }
}