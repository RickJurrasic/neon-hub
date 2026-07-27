<?php

namespace App\Services\Telemetry\Collectors;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TableLogCollector
{
    public function collect(string $table): ?array
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $last = DB::table($table)->latest()->first();

        if (! $last) {
            return null;
        }

        return [
            'timestamp' => isset($last->created_at) ? Carbon::parse($last->created_at)->format('H:i:s') : now()->format('H:i:s'),
            'system' => 'AGENT_SENTINEL',
        ];
    }
}
