<?php

namespace App\Jobs;

use App\Actions\Ai\ExecuteCommentPostAction;
use App\Actions\Ai\ExecuteCreatePostAction;
use App\Actions\Ai\ExecuteFriendRequestAction;
use App\Actions\Ai\ExecuteLikePostAction;
use App\Actions\Ai\ExecuteSendMessageAction;
use App\Events\AIActionPerformed;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAIAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $userId;

    protected string $actionType;

    protected array $payload;

    public function __construct(int $userId, string $actionType, array $payload = [])
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        usleep(mt_rand(500000, 2000000));

        if ($this->isRateLimited($user)) {
            Log::info("AI Profile [{$user->name}] is rate limited. Skipping action: {$this->actionType}");

            return;
        }

        $this->logEventStatus($user->id, 'processing');
        $this->executeAndLog($user);
    }

    protected function isRateLimited(User $user): bool
    {
        return DB::table('ai_profile_events')
            ->where('user_id', $user->id)
            ->where('executed_at', '>=', now()->subMinute())
            ->count() >= 3;
    }

    protected function executeAction(User $user): void
    {
        $actions = [
            'friend_request' => ExecuteFriendRequestAction::class,
            'send_message' => ExecuteSendMessageAction::class,
            'create_post' => ExecuteCreatePostAction::class,
            'like_post' => ExecuteLikePostAction::class,
            'comment_post' => ExecuteCommentPostAction::class,
        ];

        $actionClass = $actions[$this->actionType] ?? null;

        if (! $actionClass) {
            Log::warning("Unknown AI action type: {$this->actionType}");

            return;
        }

        app($actionClass)->execute($user, $this->payload);
    }

    private function executeAndLog(User $user): void
    {
        try {
            $this->executeAction($user);
            $this->logEventStatus($user->id, 'completed');
            event(new AIActionPerformed($user->id, $this->actionType, $this->payload));
        } catch (\Exception $e) {
            Log::error("AI Action Error [{$user->name}] - {$this->actionType}: ".$e->getMessage());
            $this->logEventStatus($user->id, 'failed');
        }
    }

    private function logEventStatus(int $userId, string $status): void
    {
        if ($status === 'processing') {
            DB::table('ai_profile_events')->insert([
                'user_id' => $userId, 'action_type' => $this->actionType, 'executed_at' => now(),
                'status' => 'processing', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return;
        }

        DB::table('ai_profile_events')
            ->where('user_id', $userId)
            ->where('action_type', $this->actionType)
            ->where('status', 'processing')
            ->update(['status' => $status, 'updated_at' => now()]);
    }
}
