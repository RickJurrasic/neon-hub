<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LlmRateLimiter
{
    public const STARTUP     = 'startup';
    public const INTERACTIVE = 'interactive';

    /**
     * Cache key for a user + bucket.
     *
     * - Bots (is_ai = true) share one global bucket and are never demo humans.
     * - Demo humans are keyed by their demo-<uuid> handle — the single signal
     *   AutoLoginDemoUser mints per browser session.
     * - Registered humans are keyed by primary key.
     */
    public function keyFor(User $user, string $bucket): string
    {
        if ($user->is_ai) {
            $handle = (string) ($user->handle ?? $user->name ?? $user->getAuthIdentifier());

            return "llm.bot.{$handle}.{$bucket}";
        }

        $handle = (string) ($user->handle ?? '');

        if ($this->isDemo($user)) {
            return "llm.demo.{$handle}.{$bucket}";
        }

        return "llm.user.{$user->getAuthIdentifier()}.{$bucket}";
    }

    /**
     * Demo detection is the handle prefix "demo-" ONLY.
     *
     * A bot with a demo- handle is NOT demo (is_ai = true). The legacy id-1
     * "Recruiter Phantom" (role = EXTERNAL_NODE, handle = @recruiter_alpha) is
     * NOT demo — it has no demo- handle. A registered user who happened to claim
     * a demo- handle IS treated as demo (browser-session budget).
     */
    public function isDemo(User $user): bool
    {
        return ! $user->is_ai && Str::startsWith((string) ($user->handle ?? ''), 'demo-');
    }

    public function max(string $bucket, User $user): int
    {
        $limits = config('neon.llm_limits', [
            'window_seconds' => 60,
            'demo' => ['startup' => 2, 'interactive' => 3],
            'registered' => ['startup' => 3, 'interactive' => 8],
        ]);

        $tier = $this->isDemo($user) ? 'demo' : 'registered';

        return (int) ($limits[$tier][$bucket] ?? $limits['registered'][$bucket] ?? 6);
    }

    public function windowSeconds(): int
    {
        return (int) config('neon.llm_limits.window_seconds', 60);
    }

    /**
     * Atomically consume one token for the user + bucket.
     * Returns true if the turn is allowed, false if the bucket is exhausted.
     */
        public function consume(string $bucket, User $user): bool
    {
        // Canonical Laravel consume pattern (tooManyAttempts + hit).
        // RateLimiter::attempt() in Laravel 13 requires a Closure callback as
        // its 3rd argument, so we call the primitives directly.
        $key = $this->keyFor($user, $bucket);

        if (RateLimiter::tooManyAttempts($key, $this->max($bucket, $user))) {
            return false;
        }

        RateLimiter::hit($key, $this->windowSeconds());

        return true;
    }

    public function attempts(string $bucket, User $user): int
    {
        return RateLimiter::attempts($this->keyFor($user, $bucket));
    }

        public function retryAfter(string $bucket, User $user): int
    {
        // Laravel's RateLimiter exposes "seconds until the window resets" as
        // availableIn() (there is no retryAfter() method on this class).
        return (int) RateLimiter::availableIn($this->keyFor($user, $bucket));
    }

    public function clear(string $bucket, User $user): void
    {
        RateLimiter::clear($this->keyFor($user, $bucket));
    }
}
