<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public array $data
    ) {}

    /**
     * Definice dat, která skutečně poletí do prohlížeče.
     * Zde vracíme kompletní data z Jobu, aby frontend měl i conversation_id atd.
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->data,
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }
}
