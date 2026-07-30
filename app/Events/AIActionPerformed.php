<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIActionPerformed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro broadcasting.
     *
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly int $userId,
        public readonly string $actionType,
        public readonly array $payload = []
    ) {}

    /**
     * Získat kanál, na kterém se má událost vysílat.
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('ai-actions.' . $this->userId);
    }

    /**
     * Data, která se pošlou přes WebSocket na frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'action_type' => $this->actionType,
            'payload' => $this->payload,
        ];
    }
}