<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        //
        //    Step 1: session UID wins – it’s the authoritative persistent identity
        //    preserved across session regeneration.
        $sessionId = $request->session()->getId();
        $cacheKey = 'demo-user-uid:'.$sessionId;
        $lockKey = 'demo-user-init:'.$sessionId;

        $demoUid = $request->session()->get('demo_uid');
        if (is_string($demoUid)) {
            // Session UID exists – use it. Synchronize to cache so the next
            // request from this session can hit the fast path.
            Cache::put($cacheKey, $demoUid, 3600);
            $demoUser = $this->getOrCreateDemoUser($demoUid, $lockKey);
        } else {
            // Step 2: if no session UID, check shared cache as fallback
            $cachedUid = Cache::get($cacheKey);
            if (is_string($cachedUid)) {
                // Cache has the UID – put into session and proceed
                $request->session()->put('demo_uid', $cachedUid);
                $demoUser = $this->getOrCreateDemoUser($cachedUid, $lockKey);
            } else {
                // Step 3: both absent – initialize under lock protection
                Cache::lock($lockKey, 10)->block(5, function () use ($request, $cacheKey) {
                    // Double-check inside lock (another thread might have initialized)
                    $cachedUid = Cache::get($cacheKey);
                    if (is_string($cachedUid)) {
                        // Another thread already initialized – populate session
                        $request->session()->put('demo_uid', $cachedUid);
                    } else {
                        // Still absent – generate and store atomically
                        $demoUid = 'demo-'.Str::uuid()->toString();
                        Cache::put($cacheKey, $demoUid, 3600); // 1 hour TTL
                        $request->session()->put('demo_uid', $demoUid);
                    }
                });

                // After lock, UID is guaranteed to be in cache and session
                $demoUid = $request->session()->get('demo_uid');
                $demoUser = $this->getOrCreateDemoUser($demoUid, $lockKey);
            }
        }

        // 4. Přihlásíme unikátního demo uživatele a obnovíme session proti fixation.
        Auth::login($demoUser);
        $request->session()->regenerate();

        return $next($request);
    }

    /**
     * Resolve the human demo user for the given demo UID with strict concurrency safety.
     *
     * Two concurrent requests for the same UID must NOT create two separate users.
     * The lock is based on the UID itself, so it serializes user lookup/create
     * regardless of how the UID was obtained.
     */
    private function getOrCreateDemoUser(string $demoUid, string $lockKey): User
    {
        $email = $demoUid.'@neonhub.io';

        // Reuse the per-session initialization lock to serialize user creation.
        // This prevents two requests that both observe a missing user from
        // creating duplicate human demo users.
        return Cache::lock($lockKey, 10)->block(5, function () use ($email, $demoUid) {
            // Defensive re-check – another thread may have created the user while we waited
            return User::where('email', $email)
                ->where('is_ai', false)
                ->first()
                ?? User::create([
                    'name' => 'Demo Surfer',
                    'email' => $email,
                    'handle' => $demoUid,
                    'password' => Str::random(24),
                    'is_ai' => false,
                    'trust_level' => 50,
                    'latency' => '24ms_STABLE',
                    'role' => 'EXTERNAL_NODE',
                ]);
        });
    }
}
