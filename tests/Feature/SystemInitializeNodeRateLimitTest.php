<?php

use App\Events\FriendRequestReceived;
use App\Events\NewActivityAlert;
use App\Events\PostCreated;
use App\Jobs\HandleAgentResponse;
use App\Services\LlmRateLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

it('allows the first Enter-System request for a demo session and dispatches the welcome sequence', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect(app(LlmRateLimiter::class)->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);
});

it('returns 429 Retry-After on the second Enter-System request and dispatches nothing', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    $this->actingAs($demo)->postJson('/system/initialize-node')->assertOk(); // 1st: granted

        $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson(['message' => 'AI_RATE_LIMITED']);

    expect(app(LlmRateLimiter::class)->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1); // no extra dispatch
});

it('returns 429 without side effects when the budget is pre-exhausted', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    $limiter = app(LlmRateLimiter::class);
    app('cache')->flush();

        $limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $demo); // 1st: succeeds, attempts=1
    expect($limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBeFalse(); // 2nd: rejected (demo cap=1)

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1);

    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertJson(['message' => 'AI_RATE_LIMITED']);

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(0); // zero dispatch
});

it('keeps Enter-System budgets isolated between demo sessions', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demoA = makeDemoUser();
    $demoB = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    $limiter = app(LlmRateLimiter::class);
    $limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $demoA); // 1
    $limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $demoA); // 2 — demo cap for A

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demoB))->toBe(0); // B untouched

    $this->actingAs($demoB)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demoB))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);
});

it('resets the demo Enter-System budget after the rate-limit window elapses', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    $limiter = app(LlmRateLimiter::class);
    app('cache')->flush();

    $limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $demo); // 1
    $limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $demo); // 2 — demo cap

    Carbon::setTestNow(now()->addHour()->addMinute()); // simulate window passage

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(0);

    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);

    Carbon::setTestNow();
});
