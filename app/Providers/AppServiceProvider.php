<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // InfinityFree's MySQL caps index keys at 1000 bytes; utf8mb4
        // VARCHAR(255) would need 1020 bytes, so keep default strings at 191.
        Schema::defaultStringLength(191);
    }
}
