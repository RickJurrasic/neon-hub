<?php

use App\Ai\Agents\SentinelAgent;
use Laravel\Ai\Responses\AgentResponse;

// 1. UNIT TEST: Rychlý test, který nevolá reálné API (Mocking)
it('can prompt the sentinel agent', function () {
    // Vytvoříme falešnou odpověď
    $mockResponse = Mockery::mock(AgentResponse::class);
    $mockResponse->shouldReceive('__toString')->andReturn('Odpověď od Sentinela');
    $mockResponse->text = 'Odpověď od Sentinela';

    // Vytvoříme mock našeho agenta
    $agent = Mockery::mock(SentinelAgent::class)->makePartial();
    $agent->shouldReceive('prompt')
        ->once()
        ->with('Ahoj')
        ->andReturn($mockResponse);

    $response = $agent->prompt('Ahoj');

    expect($response->text)->toBe('Odpověď od Sentinela');
});

// 2. INTEGRATION TEST: Testuje, jestli je API Googlu dostupné
it('can reach the gemini api', function () {
    $sentinel = SentinelAgent::make();
    $response = $sentinel->prompt('Ahoj, jsi tam?');

    expect($response->text)->not->toBeEmpty();
})->group('integration'); // Spouštět jen když chceš ověřit reálné připojení
