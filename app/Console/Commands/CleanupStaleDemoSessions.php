<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Services\ActiveDemoUsers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Automatický cleanup dat opuštěných demo sessions.
 *
 * Session-centric approach:
 *   1. Najdeme všechny demo uživatele (is_ai=false, handle LIKE 'demo-%', id != 1)
 *   2. Pro každého zkontrolujeme, zda je heartbeat stále živý (ActiveDemoUsers::TTL_SECONDS)
 *   3. Pokud heartbeat vypršel, uživatel je stale
 *   4. Odstraníme jeho session-owned data
 *   5. Odstraníme samotného demo uživatele
 *
 * Cleanup je idempotentní — druhý běh nic nerozbije.
 * Nepoužívá truncate().
 */
class CleanupStaleDemoSessions extends Command
{
    protected $signature = 'cleanup:stale-demo-sessions
        {--dry-run : Show what would be cleaned without actually deleting}';

    protected $description = 'Remove data owned by abandoned demo sessions.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $staleIds = $this->findStaleDemoUserIds();

        if (empty($staleIds)) {
            $this->info('No stale demo sessions found.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d stale demo session(s).', count($staleIds)));

        foreach ($staleIds as $userId) {
            $this->cleanupDemoUser((int) $userId, $dryRun);
        }

        return self::SUCCESS;
    }

    /**
     * @return list<int>
     */
    private function findStaleDemoUserIds(): array
    {
        $candidates = DB::table('users')
            ->where('is_ai', false)
            ->where('id', '!=', ActiveDemoUsers::LEGACY_GLOBAL_USER_ID)
            ->where('handle', 'like', 'demo-%')
            ->get(['id']);

        $stale = [];

        foreach ($candidates as $candidate) {
            $id = (int) $candidate->id;

            // Heartbeat TTL = 300s. Missing/expired heartbeat means stale.
            if (! Cache::has(ActiveDemoUsers::ACTIVE_KEY.':'.$id)) {
                $stale[] = $id;
            }
        }

        return $stale;
    }

    private function cleanupDemoUser(int $userId, bool $dryRun): void
    {
        $label = "demo user {$userId}";

        // Acquire per-user lifecycle lock to prevent race condition with heartbeat refresh
        $lock = Cache::lock(ActiveDemoUsers::LIFECYCLE_KEY.':'.$userId, 60);

        if (! $lock->block(3)) {
            $this->line("  [skipped] Lock held by another process: {$label}");
            return;
        }

        try {
            // Double-check heartbeat status after acquiring lock
            if (Cache::has(ActiveDemoUsers::ACTIVE_KEY.':'.$userId)) {
                $this->line("  [skipped] Heartbeat refreshed during wait: {$label}");
                return;
            }

            if ($dryRun) {
                $this->info("  [dry-run] Would clean: {$label}");

                return;
            }

            Log::channel('stack')->info('Cleaning stale demo session.', ['user_id' => $userId]);

            DB::transaction(function () use ($userId): void {
            // 1. Delete agent_conversation_messages for conversations owned by this user
            $conversationIds = DB::table('agent_conversations')
                ->where('user_id', $userId)
                ->pluck('id')
                ->toArray();

            if (! empty($conversationIds)) {
                DB::table('agent_conversation_messages')
                    ->whereIn('conversation_id', $conversationIds)
                    ->delete();

                DB::table('agent_conversations')
                    ->whereIn('id', $conversationIds)
                    ->delete();
            }

            // 2. Friendships, where this user is sender OR recipient
            Friendship::query()
                ->where(function ($q) use ($userId): void {
                    $q->where('sender_id', $userId)
                        ->orWhere('recipient_id', $userId);
                })
                ->delete();

            // 3. Posts owned by this demo session OR authored by this user
            $postIds = Post::query()
                ->where('demo_owner_id', $userId)
                ->orWhere('user_id', $userId)
                ->pluck('id')
                ->toArray();

            if (! empty($postIds)) {
                // Delete likes on these posts
                Like::query()->whereIn('post_id', $postIds)->delete();
                // Delete comments on these posts
                Comment::query()->whereIn('post_id', $postIds)->delete();
                // Delete the posts themselves
                Post::query()->whereIn('id', $postIds)->delete();
            }

            // 4. Likes made by this demo user on other posts
            Like::query()->where('user_id', $userId)->delete();

            // 5. Comments made by this demo user
            Comment::query()->where('user_id', $userId)->delete();

            // 6. Notifications where this user is the notifiable
            DB::table('notifications')
                ->where('notifiable_type', User::class)
                ->where('notifiable_id', $userId)
                ->delete();

            // 7. Finally, delete the demo user themselves
            DB::table('users')
                ->where('id', $userId)
                ->delete();
        });

        // Cleanup cache keys for this user
        try {
            Cache::forget(ActiveDemoUsers::ACTIVE_KEY.':'.$userId);
            Cache::forget(ActiveDemoUsers::SCHED_NEXT_KEY.':'.$userId);
        } catch (\Exception $e) {
            Log::channel('stack')->warning('Failed to cleanup cache keys for demo user '.$userId, [
                'exception' => $e,
            ]);
        }

        $this->info("  Cleaned: {$label}");
        } finally {
            $lock->release();
        }
    }
}
