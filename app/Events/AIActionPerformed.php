<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel; // ZMĚNA: Správný Laravel import
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AIActionPerformed implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $userId;

    public $actionType;

    public $payload;

    public function __construct(int $userId, string $actionType, array $payload = [])
    {
        $this->userId = $userId;
        $this->actionType = $actionType;
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('ai-actions.'.$this->userId);
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'action_type' => $this->actionType,
            'payload' => $this->payload,
        ];
    }
}
