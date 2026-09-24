<?php

use App\Events\FriendRequestReceived;
use App\Events\NewActivityAlert;
use App\Events\PostCreated;
use App\Jobs\HandleAgentResponse;
use App\Services\LlmRateLimiter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

it('allows the first Enter System after the demo budget resets', function (): void {
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

it('returns 429 with Retry-After on the second Enter System request', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    // consume the first budget manually
    app(LlmRateLimiter::class)->consume(LlmRateLimiter::ENTER_SYSTEM, $demo);

    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');

    Bus::assertNotDispatched(HandleAgentResponse::class);
});

it('blocks Enter System when the demo budget is already exhausted', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    // exhaust the budget
    app(LlmRateLimiter::class)->consume(LlmRateLimiter::ENTER_SYSTEM, $demo);
    app(LlmRateLimiter::class)->consume(LlmRateLimiter::ENTER_SYSTEM, $demo);

    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After');

    Bus::assertNotDispatched(HandleAgentResponse::class);
});

it('keeps Enter System budgets isolated across demo users', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demoA = makeDemoUser();
    $demoB = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    // demoA exhausts budget
    app(LlmRateLimiter::class)->consume(LlmRateLimiter::ENTER_SYSTEM, $demoA);
    app(LlmRateLimiter::class)->consume(LlmRateLimiter::ENTER_SYSTEM, $demoA);

    // demoB still has budget
    $this->actingAs($demoB)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);
});

it('resets the demo Enter-System budget after the rate-limit window elapses', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    $limiter = app(LlmRateLimiter::class);
    app('cache')->flush();

    // First Enter System — granted
    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1);

    // Simulate rate-limit window passage
    Carbon::setTestNow(now()->addHour()->addMinute());

    // Second Enter System after window — session flag blocks it, not rate limiter
    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_ALREADY_INITIALIZED']);

    expect($limiter->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(0)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1); // no extra dispatch
});

it('dispatches the welcome sequence exactly once per browser session (session-scoped idempotency)', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    // First Enter System in this session
    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect(app(LlmRateLimiter::class)->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);

    // Second Enter System in the same session — must be blocked by session flag, not rate limit
    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_ALREADY_INITIALIZED']);

    expect(app(LlmRateLimiter::class)->attempts(LlmRateLimiter::ENTER_SYSTEM, $demo))->toBe(1)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);
});

it('isolates session-scoped idempotency between different browser sessions (Session A ≠ Session B)', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([FriendRequestReceived::class, NewActivityAlert::class, PostCreated::class]);

    $demo = makeDemoUser();
    makeBotUser(['name' => 'SENTINEL_01']);

    app('cache')->flush();

    // Session A — first Enter System
    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);

    // Simulate a new browser session for Session B
    $this->flushSession();
    app('cache')->flush();

    // Session B — first Enter System in this new session
    $this->actingAs($demo)
        ->postJson('/system/initialize-node')
        ->assertOk()
        ->assertJson(['status' => 'NODE_INITIALIZED']);

    expect(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(2);
});
