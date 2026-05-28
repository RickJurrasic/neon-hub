<?php

namespace App\Jobs;

use App\Events\FriendRequestReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendFriendRequest implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId, public int $senderId = 999 // Tvůj bot
    ) {}

    public function handle(): void
    {
        // 1. Tady by byla tvoje logika pro databázi
        // Např.: Friendship::create([...]);

        // 2. Odpálení eventy, která poletí přes Reverb do frontendu
        event(new FriendRequestReceived($this->userId, [
            'id' => $this->senderId,
            'name' => 'CYBER_UNIT_01',
            'avatar_url' => '/images/avatars/unit_01.png',
        ]));
    }
}
