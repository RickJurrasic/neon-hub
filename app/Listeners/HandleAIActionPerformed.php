<?php

namespace App\Listeners;

use App\Events\AIActionPerformed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class HandleAIActionPerformed implements ShouldQueue
{
    public function handle(AIActionPerformed $event)
    {
        // Broadcast is handled automatically by ShouldBroadcastNow
        // Additional logic can be added here if needed
        Log::info("AI action broadcasted: {$event->actionType} for user {$event->userId}");
    }
}
