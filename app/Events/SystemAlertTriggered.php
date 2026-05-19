<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
// 🚨 ZMĚNA: Importujeme ShouldBroadcastNow místo ShouldBroadcast
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 🚨 ZMĚNA: Třída teď implementuje ShouldBroadcastNow
class SystemAlertTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoříme novou instanci události s veřejnou zprávou.
     */
    public function __construct(public string $message)
    {
        // PHP 8+ automaticky z proměnné v konstruktoru udělá vlastnost třídy
    }

    /**
     * Definujeme kanály, na kterých má událost vysílat.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('system-alerts'),
        ];
    }
}
