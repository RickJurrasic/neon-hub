<?php

namespace App\Services\Telemetry;

use App\Services\Telemetry\Collectors\TableLogCollector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ActivityStream
{
    public function __construct(private TableLogCollector $collector)
    {
    }

    public function get(): array
    {
        $stream = [];
        $tables = [
            ['likes', 'User interaction detected: Post reaction logged in network map.', 'info'],
            ['agent_conversation_messages', 'Agent generating response packet for message thread.', 'idle'],
            ['posts', 'Content indexed: New post added to global node.', 'warning'],
        ];

        foreach ($tables as [$table, $message, $type]) {
            $data = $this->collector->collect($table);
            if ($data) {
                $stream[] = array_merge($data, ['message' => $message, 'type' => $type]);
            }
        }

        $this->appendQueueAlert($stream);

        return $stream;
    }

    private function appendQueueAlert(array &$stream): void
    {
        try {
            $pending = (int) (Queue::size() ?? 0);
            if ($pending > 0) {
                $stream[] = [
                    'timestamp' => now()->format('H:i:s'),
                    'system' => 'AGENT_SENTINEL',
                    'message' => "Alert: {$pending} tasks currently pending.",
                    'type' => 'warning',
                ];
            }
        } catch (\Throwable $e) {
            Log::debug('Queue size check failed: '.$e->getMessage());
        }
    }
}
