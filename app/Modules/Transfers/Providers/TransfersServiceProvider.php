<?php

namespace App\Modules\Transfers\Providers;

use Illuminate\Support\ServiceProvider;

class TransfersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}