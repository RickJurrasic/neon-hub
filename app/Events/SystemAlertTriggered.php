<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets; // Změna: PrivateChannel
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SystemAlertTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $userId, public string $message)
    {
    }

    public function broadcastOn(): array
    {
        // Tohle se musí shodovat s window.Echo.private(...) ve frontend servisu
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'SystemAlertTriggered'; // Teď máš jistotu, jak se event jmenuje
    }
}
