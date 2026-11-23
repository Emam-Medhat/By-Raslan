<?php

namespace App\Modules\Notifications\Providers;

use App\Modules\Notifications\Services\NotificationService;
use Illuminate\Support\ServiceProvider;

class NotificationsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NotificationService::class, fn () => new NotificationService());
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/migrations');
    }
}