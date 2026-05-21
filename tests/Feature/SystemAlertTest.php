<?php

use App\Events\SystemAlertTriggered;
use Illuminate\Support\Facades\Event;

test('test route successfully dispatches websocket event to reverb', function () {
    // 1. Prevent real broadcasting by creating a safe event fake
    Event::fake();

    // 2. Simulate hitting the /test-signal endpoint
    $response = $this->get('/test-signal');

    // 3. Assert that the backend responds with a success status (HTTP 200)
    $response->assertStatus(200);

    // 4. Assert that the specific event was actually dispatched
    Event::assertDispatched(SystemAlertTriggered::class);
});
