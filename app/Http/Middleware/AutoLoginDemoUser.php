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
        // Pokud uživatel ještě není přihlášený, natvrdo ho přihlásíme jako demo usera
        if (! Auth::check()) {
            $demoUser = User::where('email', 'demo@neonhub.io')->first();

            if ($demoUser) {
                Auth::login($demoUser);
                // Vygenerujeme session pro uložení stavu
                $request->session()->regenerate();
            }
        }

        return $next($request);
    }
}
