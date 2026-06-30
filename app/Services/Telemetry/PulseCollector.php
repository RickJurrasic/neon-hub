<?php

namespace App\Services\Telemetry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PulseCollector
{
    public function getMetric(string $type): int
    {
        if (! Schema::hasTable('pulse_aggregates')) {
            return 0;
        }
        $row = DB::table('pulse_aggregates')->where('type', $type)->latest('date')->first();

        return $row ? (json_decode($row->value, true)['count'] ?? 0) : 0;
    }
}
