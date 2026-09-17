<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ActiveDemoUsers;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refreshes the "currently active demo human" heartbeat on every authenticated
 * web request. The scheduler (which runs in a background process with no HTTP
 * session) cannot call auth()/session(), so it relies on these heartbeats to
 * discover which demo humans are actually online and target them instead of a
 * hard-coded recipient.
 *
 * Runs after AutoLoginDemoUser in the web stack (see bootstrap/app.php) and only
 * records real, per-session demo humans; bots and the legacy Recruiter Phantom
 * (id 1) are intentionally never recorded as eligible.
 */
class RegisterDemoActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user instanceof User
            && ! $user->is_ai
            && $user->id !== 1
            && str_starts_with((string) ($user->handle ?? ''), 'demo-')) {
            ActiveDemoUsers::record((int) $user->id);
        }

        return $response;
    }
}
