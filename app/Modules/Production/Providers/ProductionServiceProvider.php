<?php

namespace App\Modules\Production\Providers;

use Illuminate\Support\ServiceProvider;

class ProductionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}