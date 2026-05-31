<?php

namespace App\Jobs;

use App\Actions\SendFriendRequestAction;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFriendRequest implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $botName = 'SENTINEL_01' // Tady máš botName, ne senderId!
    ) {}

    public function handle(SendFriendRequestAction $action): void
    {
        // Najdeme bota podle jména, které jsme si uložili v konstruktoru
        $bot = User::where('name', $this->botName)->firstOrFail();

        // Předáme ID bota a ID uživatele
        $action->execute($bot->id, $this->userId);
    }
}
