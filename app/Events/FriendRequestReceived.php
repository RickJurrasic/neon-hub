<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro přijatou žádost o přátelství.
     *
     * @param  array<string, mixed>  $data  Data o odesílateli / žádosti pro Pinia store.
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $data
    ) {}

    /**
     * Získat privátní kanál příjemce.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    /**
     * Data předávaná přes WebSocket na frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }

    /**
     * Název události pro WebSocket listener.
     */
    public function broadcastAs(): string
    {
        return 'FriendRequestReceived';
    }
}
