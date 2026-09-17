<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Lets background code (the scheduler / queue worker) identify demo humans
 * that are actively browsing the app, even though it has no HTTP session.
 *
 * Web requests refresh a per-user heartbeat via the RegisterDemoActivity
 * middleware; the scheduler reads those heartbeats to discover which demo
 * humans are actually online. Storage is the project's default cache store
 * (array in tests, database-backed in production), which is shared across
 * HTTP and queue/scheduler processes, so no dedicated table or session is
 * required.
 *
 * Session liveness and scheduler timing are deliberately SEPARATE concerns:
 *   - demo:active_user:{id}      -> "is this session currently online?"  (heartbeat)
 *   - demo:sched_next:{id}       -> "is this session's timer elapsed?"    (timing only)
 *   - demo:sched_processing:{id} -> in-progress dispatch lock (dedupe)
 *
 * demo:sched_next:{id} is NEVER used as proof of activity: an expired
 * heartbeat removes a session from eligibility on the very next tick, even
 * if its scheduler timer happens to still be in the past.
 */
class ActiveDemoUsers
{
    /** Cache key prefix; each active human has key "demo:active_user:{id}". */
    public const string ACTIVE_KEY = 'demo:active_user';

    /** Per-session scheduler timing state only: next eligible tick time. */
    public const string SCHED_NEXT_KEY = 'demo:sched_next';

    /** Per-session processing lock (atomic add, auto-expires -> no deadlocks). */
    public const string SCHED_LOCK_KEY = 'demo:sched_processing';

    /**
     * Seconds a heartbeat stays valid. Abandoned browser sessions age out,
     * so the scheduler stops messaging idle demos.
     */
    public const int TTL_SECONDS = 300;

    /** First scheduler action fires within [INITIAL_DELAY_MIN, INITIAL_DELAY_MAX] of first sighting. */
    public const int INITIAL_DELAY_MIN = 30;

    /** @var int */
    public const int INITIAL_DELAY_MAX = 40;

    /** Subsequent actions fire within [INTERVAL_MIN, INTERVAL_MAX] seconds. */
    public const int INTERVAL_MIN = 30;

    /** @var int */
    public const int INTERVAL_MAX = 90;

    /** How long an in-progress processing lock is honoured before auto-releasing. */
    public const int LOCK_TTL_SECONDS = 10;

    /** The legacy shared Recruiter Phantom identity (id 1) is never a target. */
    public const int LEGACY_GLOBAL_USER_ID = 1;

    /**
     * Refresh this demo human's activity heartbeat (called from the web stack).
     */
    public static function record(int $userId): void
    {
        Cache::put(
            self::ACTIVE_KEY.':'.$userId,
            true,
            now()->addSeconds(self::TTL_SECONDS),
        );
    }

    /**
     * The authoritative set of currently active demo sessions: demo humans
     * (is_ai = false, handle like 'demo-%', id != legacy global) that still
     * hold a live heartbeat. One id per active browser session.
     *
     * @return list<int>
     */
    public static function activeIds(): array
    {
        $candidates = User::query()
            ->where('is_ai', false)
            ->where('id', '!=', self::LEGACY_GLOBAL_USER_ID)
            ->where('handle', 'like', 'demo-%')
            ->get(['id']);

        $active = [];

        foreach ($candidates as $candidate) {
            $id = (int) $candidate->id;

            if (Cache::has(self::ACTIVE_KEY.':'.$id)) {
                $active[] = $id;
            }
        }

        return $active;
    }

    /**
     * Lazily seed a session's first scheduler deadline on first sighting while
     * the session is active. Deferred from session-open on purpose: this keeps
     * the first scheduler action (~30-40s out) independent from the three
     * initial Enter-System events dispatched in NeonHubController.
     *
     * @return Carbon The deadline this session was seeded with.
     */
    public static function initializeScheduler(int $userId): Carbon
    {
        $deadline = now()->addSeconds(random_int(self::INITIAL_DELAY_MIN, self::INITIAL_DELAY_MAX));

        Cache::put(
            self::SCHED_NEXT_KEY.':'.$userId,
            $deadline->timestamp,
            now()->addSeconds(self::TTL_SECONDS),
        );

        return $deadline;
    }

    /**
     * When should this session's scheduler fire next? Returns null when the
     * session has never been seeded (so the caller can initialize it).
     *
     * The deadline is stored as a Unix timestamp integer, which is cache-safe:
     * it survives unserialize under any serializable_classes setting (the
     * production database store runs with serializable_classes => false,
     * which turns a cached Carbon back into a __PHP_Incomplete_Class). Legacy
     * object entries are therefore treated as not-seeded so the scheduler
     * re-seeds them automatically. Such objects are never cast to string.
     */
    public static function nextActionAt(int $userId): ?Carbon
    {
        $value = Cache::get(self::SCHED_NEXT_KEY.':'.$userId);

        // Stale/malformed entries (e.g. a leftover serialized Carbon that the
        // cache store materialized as a __PHP_Incomplete_Class) are not usable
        // deadlines: treat them as not-seeded so tick() re-seeds this session
        // and overwrites the bad value with a cache-safe scalar timestamp.
        if (is_object($value) || ! is_numeric($value)) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value);
    }

    /**
     * Has this session's scheduler timer elapsed (or is it not yet seeded)?
     */
    public static function isEligible(int $userId): bool
    {
        $next = self::nextActionAt($userId);

        return $next === null || $next->isPast();
    }

    /**
     * Atomically claim a session so a duplicate tick (same or concurrent
     * process) cannot dispatch two actions for it. Auto-expires after
     * LOCK_TTL_SECONDS, so it can never deadlock a dead process.
     */
    public static function acquireLock(int $userId): bool
    {
        return (bool) Cache::add(
            self::SCHED_LOCK_KEY.':'.$userId,
            1,
            now()->addSeconds(self::LOCK_TTL_SECONDS),
        );
    }

    /**
     * Release the per-session lock. Best-effort; safe even if the TTL already
     * elapsed on its own.
     */
    public static function releaseLock(int $userId): void
    {
        Cache::forget(self::SCHED_LOCK_KEY.':'.$userId);
    }

    /**
     * Advance this session's independent next deadline using the configured
     * interval plus per-session jitter.
     */
    public static function scheduleNext(int $userId): void
    {
        $delay = random_int(self::INTERVAL_MIN, self::INTERVAL_MAX);

        Cache::put(
            self::SCHED_NEXT_KEY.':'.$userId,
            (int) now()->timestamp + $delay,
            now()->addSeconds(self::TTL_SECONDS),
        );
    }

    /**
     * Validate a recipient id supplied through a scheduler payload: it must be
     * a real demo human (exists, is_ai = false, demo- handle) and must not be
     * the sender or the legacy global id 1. Defense in depth: the executors
     * fail safe even if a scheduler payload were ever wrong.
     */
    public static function isValidRecipient(int $userId, int $senderId): bool
    {
        if ($userId <= 0 || $userId === $senderId || $userId === self::LEGACY_GLOBAL_USER_ID) {
            return false;
        }

        /** @var User|null $user */
        $user = User::find($userId);

        if ($user === null || $user->is_ai) {
            return false;
        }

        return str_starts_with((string) ($user->handle ?? ''), 'demo-');
    }
}
