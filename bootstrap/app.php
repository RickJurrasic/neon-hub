<?php

use App\Http\Middleware\AutoLoginDemoUser;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 1. Zruš ten prepend, ten nám shazuje session!

        // 2. Všechno naskládáme do "append" v přesném pořadí, v jakém to má jít po sobě:
        $middleware->web(append: [
            AutoLoginDemoUser::class,     // První se přihlásí uživatel (session už běží z jádra web skupiny)
            HandleInertiaRequests::class,  // Druhá se spustí Inertia a už uvidí přihlášeného uživatele
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
