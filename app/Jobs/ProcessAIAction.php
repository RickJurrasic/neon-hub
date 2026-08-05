<?php

namespace App\Jobs;

use App\Ai\Agents\Actions\Ai\ExecuteCommentPostAction;
use App\Ai\Agents\Actions\Ai\ExecuteCreatePostAction;
use App\Ai\Agents\Actions\Ai\ExecuteFriendRequestAction;
use App\Ai\Agents\Actions\Ai\ExecuteLikePostAction;
use App\Ai\Agents\Actions\Ai\ExecuteSendMessageAction;
use App\Events\AIActionPerformed;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAIAction implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Počet pokusů o opakování jobu při selhání.
     */
    public int $tries = 3;

    /**
     * Proleva mezi opakovanými pokusy (v sekundách) nebo pole pro backoff.
     */
    public array $backoff = [15, 30, 60];

    /**
     * Maximální doba běhu jobu (v sekundách).
     */
    public int $timeout = 60;

    /**
     * Mapa dostupných AI akcí a jejich vykonávacích tříd.
     */
    private const ACTION_MAP = [
        'friend_request' => ExecuteFriendRequestAction::class,
        'send_message' => ExecuteSendMessageAction::class,
        'create_post' => ExecuteCreatePostAction::class,
        'like_post' => ExecuteLikePostAction::class,
        'comment_post' => ExecuteCommentPostAction::class,
    ];

    public function __construct(
        public readonly int $userId,
        public readonly string $actionType,
        public readonly array $payload = []
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning("ProcessAIAction skipped: User {$this->userId} not found.");
            return;
        }

        // Pokud je uživatel/AI v daném momentu rate limited,
        // místo blokování přes usleep() vrátíme job zpět do fronty s odloženou platností (např. za 20 sekund).
        if ($this->isRateLimited($user->id)) {
            Log::info("ProcessAIAction rate limited: AI Profile [{$user->name}] for action '{$this->actionType}'. Releasing back to queue.");
            
            $this->release(20);
            return;
        }

        $eventId = $this->createEventLog($user->id);

        $this->executeAndLog($user, $eventId);
    }

    private function isRateLimited(int $userId): bool
    {
        return DB::table('ai_profile_events')
            ->where('user_id', $userId)
            ->where('executed_at', '>=', now()->subMinute())
            ->count() >= 3;
    }

    private function executeAndLog(User $user, int $eventId): void
    {
        $actionClass = self::ACTION_MAP[$this->actionType] ?? null;

        if (! $actionClass) {
            Log::warning("ProcessAIAction: Unknown action type '{$this->actionType}' for User {$user->id}");
            $this->updateEventStatus($eventId, 'failed');

            return;
        }

        try {
            app($actionClass)->execute($user, $this->payload);

            $this->updateEventStatus($eventId, 'completed');

            event(new AIActionPerformed($user->id, $this->actionType, $this->payload));
        } catch (Throwable $e) {
            Log::error("ProcessAIAction Error [{$user->name}] - {$this->actionType}: {$e->getMessage()}", [
                'exception' => $e,
                'payload' => $this->payload,
            ]);

            $this->updateEventStatus($eventId, 'failed');
            
            // Re-throw, aby Laravel věděl, že job selhal a měl případně pokus opakovat
            throw $e;
        }
    }

    private function createEventLog(int $userId): int
    {
        return DB::table('ai_profile_events')->insertGetId([
            'user_id' => $userId,
            'action_type' => $this->actionType,
            'status' => 'processing',
            'executed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateEventStatus(int $eventId, string $status): void
    {
        DB::table('ai_profile_events')
            ->where('id', $eventId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }

    /**
     * Ošetření trvalého selhání jobu.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("ProcessAIAction failed permanently for User {$this->userId} [{$this->actionType}]: {$exception->getMessage()}");
    }
}