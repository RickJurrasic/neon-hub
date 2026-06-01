<?php

namespace App\Jobs;

use App\Actions\SendFriendRequestAction;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendFriendRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $botName = 'SENTINEL_01'
    ) {}

    public function handle(SendFriendRequestAction $action): void
    {
        $bot = User::where('name', $this->botName)->firstOrFail();

        $action->execute($bot->id, $this->userId);
    }
}
