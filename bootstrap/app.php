<?php

use App\Modules\Identity\Http\Middleware\CheckPermission;
use App\Modules\Identity\Http\Middleware\EnsureActive;
use App\Modules\Orders\Services\ReservationService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'perm' => CheckPermission::class,
            'active' => EnsureActive::class,
            'shift' => \App\Modules\Sales\Http\Middleware\EnsureActiveShift::class,
        ]);

        // Public customer API endpoints are stateless (no CSRF).
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Reservations (الحجوزات): at 00:00 every day, first convert
        // today's pending reservations into orders, then generate the
        // factory report for tomorrow's reservations. The scheduler only
        // runs when `php artisan schedule:run` is executed (cron, every
        // minute) on the server — see the reservations docs in
        // ReservationService.
        $schedule->call(fn () => app(ReservationService::class)->convertDueToday())
            ->dailyAt('00:00')->name('reservations.convert-due');
        $schedule->call(fn () => app(ReservationService::class)->generateFactoryReportForTomorrow())
            ->dailyAt('00:00')->name('reservations.factory-report');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();