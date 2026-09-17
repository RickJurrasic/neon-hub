<?php

use App\Ai\Agents\AIActionScheduler;
use App\Http\Middleware\AutoLoginDemoUser;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RegisterDemoActivity;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AutoLoginDemoUser::class,
            RegisterDemoActivity::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        Log::info('Scheduler: Initializing AI profiles scheduler.');

        $schedule->call(function (): void {
            AIActionScheduler::tick();
        })
            ->everyThirtySeconds()
            ->name('ai-profile-scheduler')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
