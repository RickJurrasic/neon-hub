<?php

namespace App\Http\Controllers;

use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;

class CoreEngineController extends Controller
{
    public function __construct(
        private TelemetryService $telemetry
    ) {
    }

    public function getTelemetry(): JsonResponse
    {
        $data = $this->telemetry->getTelemetryData();

        return response()->json([
            'status' => 'ONLINE',
            'version' => '1.3.0',
            'activity_stream' => $data['activity_stream'],
            'metrics' => $data['metrics'],
        ]);
    }
}
