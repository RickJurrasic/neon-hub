<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewActivityAlert implements ShouldBroadcast
{
    public function __construct(
        public int $userId,
        public string $message // Tohle je ta tvoje zpráva
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('App.Models.User.'.$this->userId);
    }
}
