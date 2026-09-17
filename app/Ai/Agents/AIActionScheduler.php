<?php

namespace App\Ai\Agents;

use App\Jobs\ProcessAIAction;
use App\Models\User;
use App\Services\ActiveDemoUsers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * One scheduler tick for AI profiles.
 *
 * The scheduler always runs in a background process (artisan schedule:run ->
 * queue worker) and therefore has NO HTTP session and cannot call auth() or
 * session('demo_uid'). Each tick iterates the currently active demo sessions
 * (discovered via ActiveDemoUsers::activeIds() heartbeats) and, for every one
 * whose OWN independent timer has expired, dispatches a single AI action whose
 * recipient is THAT session's demo human.
 *
 * Architecture (the key invariant):
 *   - ONE global scheduler (this tick), invoked every 30s by bootstrap/app.php.
 *   - INDEPENDENT per-session timers (demo:sched_next:{id}) + jitter.
 *   - SESSION-SPECIFIC recipients: recipient_id is always the session's own
 *     human; never another session's human, never id 1, never a bot.
 *   - GLOBALLY SHARED bot actors (is_ai = true); a bot is only the sender.
 *
 * Bot/session ownership is intentionally NOT modelled in the data layer (no
 * per-session bot pool, no migration): the same global AI bot pool acts on
 * behalf of any session. Cross-session isolation is enforced by (a) routing
 * each targeted action strictly to the current session's human and (b) the
 * per-user private channel gate in routes/channels.php
 *   ($user->id === $userId), so session A's events never reach session B.
 */
class AIActionScheduler
{
    /**
     * Actions that are 1:1 and privately broadcast on App.Models.User.{recipient}:
     * they MUST be handed an explicit active demo-human recipient. Feed actions
     * broadcast on the public `posts` channel and need no recipient.
     */
    private const array TARGETED_ACTIONS = ['send_message', 'friend_request'];

    public static function tick(): void
    {
        Log::info('Scheduler: AI profile scheduler tick starting.');

        $aiUsers = User::where('is_ai', true)->get(['id', 'name']);

        if ($aiUsers->isEmpty()) {
            Log::warning('Scheduler: No AI users found.');
        }

        $actions = (array) config('ai_actions.actions', []);
        $available = array_keys($actions);

        if ($available === []) {
            Log::warning('Scheduler: No AI actions configured; aborting tick.');

            return;
        }

        foreach (ActiveDemoUsers::activeIds() as $humanId) {
            $humanId = (int) $humanId;

            // Lazily seed the first deadline on first sighting of an active
            // session. Deferring this from session-open keeps the initial
            // Enter-System events (HandleAgentResponse in NeonHubController)
            // entirely independent and never delayed by the scheduler.
            if (ActiveDemoUsers::nextActionAt($humanId) === null) {
                ActiveDemoUsers::initializeScheduler($humanId);
                Log::info("Scheduler: First sighting of demo session {$humanId}; seeded first deadline.");

                continue;
            }

            // Each session has its OWN timer; only eligible sessions fire now.
            if (! ActiveDemoUsers::isEligible($humanId)) {
                continue;
            }

            // Atomic per-session lock: prevents the same (or a concurrent) tick
            // from dispatching two actions for one session in one sweep.
            if (! ActiveDemoUsers::acquireLock($humanId)) {
                Log::info("Scheduler: Session {$humanId} dispatch already in progress; skipping.");

                continue;
            }

            $dispatched = false;

            try {
                $dispatched = self::dispatchForSession($humanId, $aiUsers, $available);
            } finally {
                ActiveDemoUsers::releaseLock($humanId);
            }

            // Advance THIS session's independent timer only when an action
            // actually fired. A session with no bots keeps retrying next tick
            // rather than silently sleeping.
            if ($dispatched) {
                ActiveDemoUsers::scheduleNext($humanId);
            }
        }

        Log::info('Scheduler: AI profile scheduler tick complete.');
    }

    /**
     * Choose one global AI bot (shared actor pool) + one configured action,
     * then dispatch a ProcessAIAction whose recipient is THIS session's human.
     *
     * @param  Collection<int, User>  $aiUsers
     * @param  array<array-key, string>  $available
     */
    private static function dispatchForSession(int $humanId, Collection $aiUsers, array $available): bool
    {
        $bot = self::resolveBot($aiUsers);

        if ($bot === null) {
            Log::warning("Scheduler: No AI bots available for session {$humanId}; skipping.");

            return false;
        }

        $action = self::pickAction($available, (int) $bot->id);
                // Every action carries the session's demo human id, so executors
        // (e.g. create_post on the public feed) can attribute demo posts to
        // the correct session even when no HTTP context is available.
        $payload = [
            'demo_owner_id' => $humanId,
        ];

        if (in_array($action, self::TARGETED_ACTIONS, true)) {
            // ALWAYS this session's own human (never another session, never id 1,
            // never a bot).
            $payload['recipient_id'] = $humanId;
        }

        ProcessAIAction::dispatch((int) $bot->id, $action, $payload);

        Log::info(
            in_array($action, self::TARGETED_ACTIONS, true)
                ? "Scheduler: session {$humanId} -> bot {$bot->id} ({$bot->name}) dispatched [{$action}] to its own human."
                : "Scheduler: session {$humanId} -> bot {$bot->id} ({$bot->name}) dispatched public [{$action}]."
        );

        return true;
    }

    /**
     * Choose an actor from the global AI pool (shared across all sessions).
     * Returns null when no AI user exists (fail closed).
     *
     * @param  Collection<int, User>  $aiUsers
     */
    private static function resolveBot(Collection $aiUsers): ?User
    {
        if ($aiUsers->isEmpty()) {
            return null;
        }

        /** @var User $bot */
        $bot = $aiUsers->random();

        return $bot;
    }

    /**
     * Choose an action for the actor, avoiding immediate repetition of the
     * last action that SAME actor executed (ai_profile_events is keyed by
     * actor, not by session). Falls back to the full set when only one
     * option remains.
     *
     * @param  array<array-key, string>  $available
     */
    private static function pickAction(array $available, int $actorId): string
    {
        if ($available === []) {
            return '';
        }

        $lastAction = DB::table('ai_profile_events')
            ->where('user_id', $actorId)
            ->orderBy('executed_at', 'desc')
            ->value('action_type');

        $candidates = $available;

        if (filled($lastAction)) {
            $filtered = array_filter($candidates, static fn (string $a): bool => $a !== $lastAction);
            $candidates = $filtered !== [] ? $filtered : $available;
        }

        return (string) ($candidates[(int) array_rand($candidates)] ?? '');
    }
}
