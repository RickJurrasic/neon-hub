<?php

namespace App\Services;

use App\Services\Telemetry\ActivityStream;
use App\Services\Telemetry\PulseCollector;
use App\Services\Telemetry\QueueMonitor;

class TelemetryService
{
    public function __construct(
        private PulseCollector $pulse,
        private QueueMonitor $queue,
        private ActivityStream $stream
    ) {
    }

    public function getTelemetryData(): array
    {
        return [
            'metrics' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'pending_jobs' => $this->queue->getSize(),
                'slow_queries_detected' => $this->pulse->getMetric('slow_queries'),
                'slow_requests_detected' => $this->pulse->getMetric('slow_routes'),
                'db_connection' => 'CONNECTED',
            ],
            'activity_stream' => $this->stream->get(),
        ];
    }
}
