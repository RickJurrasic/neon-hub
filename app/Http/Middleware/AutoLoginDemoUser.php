<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Zkontrolujeme, zda uživatel už není přihlášený
        if (! Auth::check()) {
            // 2. Najdeme demo uživatele podle emailu, nebo vezmeme ID 1 / prvního v DB
            $demoUser = User::where('email', 'demo@neonhub.io')->first() ?? User::find(1) ?? User::first();

            if ($demoUser) {
                Auth::login($demoUser);
                $request->session()->regenerate();
            }
        }

        return $next($request);
    }
}
