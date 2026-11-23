<?php

namespace App\Modules\Identity\Providers;

use App\Modules\Identity\Services\PermissionService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PermissionService::class, fn () => new PermissionService());
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');

        // In this ERP every ability name IS a permission key (e.g. "stock.edit").
        // Owner bypasses; otherwise resolve the ability as a permission check.
        Gate::before(function ($user, string $ability) {
            if (! method_exists($user, 'hasPermission')) {
                return null;
            }

            if ($user->isOwner()) {
                return true;
            }

            return $user->hasPermission($ability) ? true : null;
        });
    }
}