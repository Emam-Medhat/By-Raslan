<?php

namespace App\Modules\Trips\Providers;

use Illuminate\Support\ServiceProvider;

class TripsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}