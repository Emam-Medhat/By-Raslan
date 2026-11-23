<?php

namespace App\Modules\Returns\Providers;

use Illuminate\Support\ServiceProvider;

class ReturnsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}