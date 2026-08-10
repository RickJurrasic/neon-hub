<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewActivityAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro systémové upozornění uživatele.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $message
    ) {}

    /**
     * Získat privátní kanál konkrétního uživatele.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('App.Models.User.'.$this->userId);
    }

    /**
     * Data předávaná přes WebSocket na frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'message' => $this->message,
        ];
    }

    /**
     * Název události pro WebSocket listener na frontendu.
     */
    public function broadcastAs(): string
    {
        return 'NewActivityAlert';
    }
}
