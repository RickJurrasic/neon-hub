<?php

use App\Events\SystemAlertTriggered;
use Illuminate\Support\Facades\Event;

test('testovaci routa uspesne odpaluje websocket event do reverbu', function () {
    // 1. Zastavíme reálné střílení do sítě (vytvoříme bezpečný "fake")
    Event::fake();

    // 2. Simulujeme, že uživatel v prohlížeči navštívil /test-signal
    $response = $this->get('/test-signal');

    // 3. Ověříme, že backend odpověděl úspěchem (HTTP kód 200)
    $response->assertStatus(200);

    // 4. Ověříme, že ten náš konkrétní Event byl reálně odeslán k vysílání
    Event::assertDispatched(SystemAlertTriggered::class);
});
