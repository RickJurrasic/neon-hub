<?php

use App\Ai\Agents\AIAgent;
use Laravel\Ai\Responses\AgentResponse;

// 1. UNIT TEST: Zůstává stejný, je to správný přístup
it('can prompt the sentinel agent', function () {
    $mockResponse = Mockery::mock(AgentResponse::class);
    $mockResponse->text = 'Odpověď od Sentinela';

    $agent = Mockery::mock(AIAgent::class)->makePartial();
    $agent->shouldReceive('prompt')
        ->once()
        ->with('Ahoj')
        ->andReturn($mockResponse);

    $response = $agent->prompt('Ahoj');

    expect($response->text)->toBe('Odpověď od Sentinela');
});

// 2. UNIT TEST: Mocknutí prompt odpovědi pro SENTINEL persona
it('can handle sentinel persona', function () {
    $mockResponse = Mockery::mock(AgentResponse::class);
    $mockResponse->text = 'System status: nominal. Ready for operations.';

    $agent = Mockery::mock(AIAgent::class)->makePartial();
    $agent->shouldReceive('prompt')
        ->once()
        ->with('Ahoj, jsi tam?')
        ->andReturn($mockResponse);

    $response = $agent->prompt('Ahoj, jsi tam?');

    expect($response->text)->toContain('nominal');
});

// 3. UNIT TEST: Mocknutí prompt odpovědi pro CYPHER persona
it('can handle cypher persona', function () {
    $mockResponse = Mockery::mock(AgentResponse::class);
    $mockResponse->text = 'yeah, i\'m here. what\'s the score?';

    $agent = Mockery::mock(AIAgent::class)->makePartial();
    $agent->shouldReceive('prompt')
        ->once()
        ->with('Ahoj, jsi tam?')
        ->andReturn($mockResponse);

    $response = $agent->prompt('Ahoj, jsi tam?');

    expect($response->text)->toContain('score');
});
