<?php

use App\Services\LlmRateLimiter;
use Illuminate\Support\Facades\Log;

/*
 * Regression tests for exception information leakage in SystemController
 * (POST /system/initialize-node).
 *
 * Bug: when a Throwable was caught inside initializeNode(), the catch block
 * returned $e->getMessage() directly in the JSON response body — leaking
 * internal details (file paths, SQL fragments, API keys, config values) to
 * the HTTP client.
 *
 * Fix: the response now carries only a generic, fixed error code
 * (NODE_INIT_FAILED). The full exception is still logged server-side via
 * Log::error() for operator diagnostics.
 *
 * These tests force the catch block to execute and assert:
 *   1. The HTTP body does NOT contain the raw exception message.
 *   2. The response uses a fixed, generic error code.
 *   3. The full exception message IS written to the server log.
 */

it('returns a generic NODE_INIT_FAILED message instead of the raw exception text', function (): void {
    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);
    app('cache')->flush();
    Log::spy();

    // Bind a limiter mock whose consume() throws a RuntimeException
    // containing sensitive-looking internals. The controller's
    // catch(Throwable) will intercept it.
    $explosion = new RuntimeException(
        'Database connection refused: sql=SELECT * FROM users WHERE api_key=\'sk-9f3a8b2c1d7e6f5a4b3c2d1e0f\'; file=/app/Services/NeonHubService.php:812'
    );

    $mock = Mockery::mock(LlmRateLimiter::class);
    $mock->shouldReceive('consume')->andThrow($explosion);
    app()->instance(LlmRateLimiter::class, $mock);

    $response = $this->actingAs($demo)
        ->postJson('/system/initialize-node');

    $response->assertStatus(500)
        ->assertJson([
            'status'  => 'ERROR',
            'message' => 'NODE_INIT_FAILED',
        ]);

    // The raw exception message must never appear in the response body.
    $body = $response->getContent();
    expect($body)->not->toContain('Database connection refused')
        ->and($body)->not->toContain('api_key')
        ->and($body)->not->toContain('NeonHubService.php')
        ->and($body)->not->toContain('sk-9f3a8b2c1d7e6f5a4b3c2d1e0f')
        ->and($body)->not->toContain('sql=');

    // The full diagnostic must still reach the server log.
    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg) => $msg === 'Inicializace uzlu selhala: '.$explosion->getMessage())
        ->once();
});

it('logs the full exception while returning only the generic error code', function (): void {
    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);
    app('cache')->flush();
    Log::spy();

    $secretLeak = 'INTERNAL: REDIS_PASSWORD=redis-prod-7f3a9b2c';

    $mock = Mockery::mock(LlmRateLimiter::class);
    $mock->shouldReceive('consume')->andThrow(new RuntimeException($secretLeak));
    app()->instance(LlmRateLimiter::class, $mock);

    $response = $this->actingAs($demo)
        ->postJson('/system/initialize-node');

    $response->assertStatus(500)
        ->assertJsonFragment(['message' => 'NODE_INIT_FAILED']);

    // The sensitive string is logged (for ops) but never sent to the client.
    Log::shouldHaveReceived('error')
        ->withArgs(fn (string $msg) => $msg === 'Inicializace uzlu selhala: '.$secretLeak)
        ->once();

    $response->assertDontSee($secretLeak);
    $response->assertDontSee('REDIS_PASSWORD');
});
