<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemAlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci systémové výstrahy.
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $message
    ) {}

    /**
     * Získat privátní kanál konkrétního uživatele.
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
     * Payload předávaný přes WebSocket na frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'userId' => $this->userId,
            'message' => $this->message,
        ];
    }

    /**
     * Název události pro frontend listener (Laravel Echo).
     */
    public function broadcastAs(): string
    {
        return 'SystemAlertTriggered';
    }
}
