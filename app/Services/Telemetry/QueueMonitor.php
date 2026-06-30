<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\Queue;

class QueueMonitor
{
    public function getSize(): int
    {
        try {
            return (int) (Queue::size() ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}
