<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AutoLoginDemoUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Registrovaní i přihlášení uživatelé jsou nechávány beze změny.
        if (Auth::check()) {
            return $next($request);
        }

        // 2. Každá anonymní (demo) relase dostane vlastní unikátní lidského
        //    uživatele. Identita je klademe na session pomocí `demo_uid`,
        //    který je stabilní po celou dobu trvání session a přežije
        //    regenerate() (Laravel při rotaci ID zachovává obsah session).
        //    Díky jedinečnému uživateli jsou všechny auth()->id()-scoped
        //    operace (zprávy, konverzace, lajky, komentáře, přátelství,
        //    broadcast kanál App.Models.User.{id}) automaticky izolovované
        //    mezi jednotlivími demo relacemi.
        //    Boti (is_ai=true) zůstávají globální a nejsou nikdy voleni jako
        //    demo lidský uživatel.
        $demoUid = $request->session()->get('demo_uid');

        if (! is_string($demoUid)) {
            $demoUid = 'demo-'.Str::uuid()->toString();
            $request->session()->put('demo_uid', $demoUid);
        }

        // 3. Nikdy nepoužíváme globální "id=1" (Recruiter Phantom). Hledáme
        //    nebo vytvoříme demo uživatele výlučně pod tímto per-session
        //    emailem a vždy s is_ai=false, takže se nikdy nepromítne s boty.
        $demoUser = User::where('email', $demoUid.'@neonhub.io')
            ->where('is_ai', false)
            ->first()
            ?? User::create([
                'name' => 'Demo Surfer',
                'email' => $demoUid.'@neonhub.io',
                'handle' => $demoUid,
                'password' => Str::random(24),
                'is_ai' => false,
                'trust_level' => 50,
                'latency' => '24ms_STABLE',
                'role' => 'EXTERNAL_NODE',
            ]);

        // 4. Přihlásíme unikátního demo uživatele a obnovíme session proti fixation.
        Auth::login($demoUser);
        $request->session()->regenerate();

        return $next($request);
    }
}