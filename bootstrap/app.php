<?php

use App\Http\Middleware\AutoLoginDemoUser;
use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\ProcessAIAction;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            AutoLoginDemoUser::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        Log::info('Scheduler: Initializing AI profiles scheduler.');

        $aiUsers = User::where('is_ai', true)->get();

        if ($aiUsers->isEmpty()) {
            Log::warning('Scheduler: No AI users found.');

            return;
        }

        // Scheduler běží každou minutu, ale vybírá náhodného bota
        // A každý bot má svou náhodnou prodlevu 15-45 sekund mezi voláními
        $schedule->call(function () use ($aiUsers) {
            // Vybereme náhodného bota
            $user = $aiUsers->random();

            // Získání poslední akce pro tohoto bota
            $lastAction = DB::table('ai_profile_events')
                ->where('user_id', $user->id)
                ->orderBy('executed_at', 'desc')
                ->value('action_type');

            $actions = config('ai_actions.actions');
            $available = array_keys($actions);

            if ($lastAction) {
                $available = array_filter($available, fn ($a) => $a !== $lastAction);
            }

            if (! empty($available)) {
                $action = $available[array_rand($available)];
                Log::info("Scheduler: Dispatching job [{$action}] for user [{$user->id}] ({$user->name}).");
                ProcessAIAction::dispatch($user->id, $action);
            } else {
                // Všechny actiony byly použity, použijeme jakoukoliv
                $action = array_keys($actions)[array_rand(array_keys($actions))];
                Log::info("Scheduler: Dispatching job [{$action}] for user [{$user->id}] ({$user->name}) - reset.");
                ProcessAIAction::dispatch($user->id, $action);
            }
        })
            ->everyThirtySeconds() // Běží každých 30 sekund
            ->name('ai-profile-scheduler')
            ->withoutOverlapping();

        Log::info('Scheduler: AI profiles scheduler initialized for '.$aiUsers->count().' bots.');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
