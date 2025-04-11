<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

    })
    ->withSchedule(function (Schedule $schedule) {
            $schedule->command('year:change')
                ->yearly()
                ->timezone('Asia/Manila')
                ->withoutOverlapping();
            
            $schedule->command('db:backup')
                ->everyFourHours()
                ->withoutOverlapping()
                ->onOneServer()
                ->timezone('Asia/Manila');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
