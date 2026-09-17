<?php

use App\Actions\SendMessageAction;
use App\Events\MessageReceived;
use App\Jobs\AutoSendAgentMessage;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use App\Services\LlmRateLimiter;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('classifies demo humans by handle prefix only and applies tiered caps', function (): void {
    $limiter = app(LlmRateLimiter::class);

    $demo  = makeDemoUser();                              // demo-<uuid> handle -> demo
    $human = User::factory()->create(['is_ai' => false]); // no demo- handle -> registered

    expect($limiter->isDemo($demo))->toBeTrue()
        ->and($limiter->isDemo($human))->toBeFalse();

    expect($limiter->max(LlmRateLimiter::INTERACTIVE, $demo))->toBe(3)
        ->and($limiter->max(LlmRateLimiter::STARTUP, $demo))->toBe(2)
        ->and($limiter->max(LlmRateLimiter::INTERACTIVE, $human))->toBe(8)
        ->and($limiter->max(LlmRateLimiter::STARTUP, $human))->toBe(3);
});

it('never treats a bot as demo even if its handle starts with demo-', function (): void {
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'handle' => 'demo-oops', 'is_ai' => true]);

    expect(app(LlmRateLimiter::class)->isDemo($bot))->toBeFalse();

    $limiter = app(LlmRateLimiter::class);
    $limiter->consume(LlmRateLimiter::INTERACTIVE, $bot); // bot key (id = 0) -> global
    expect($limiter->attempts(LlmRateLimiter::INTERACTIVE, $bot))->toBe(1);
});

it('treats the legacy id-1 Recruiter Phantom as registered (not demo)', function (): void {
    User::query()->insert([
        'id' => 1,
        'name' => 'Recruiter Phantom',
        'email' => 'demo@neonhub.io',
        'handle' => '@recruiter_alpha',
        'role' => 'EXTERNAL_NODE',
        'bio' => 'Legacy shared demo identity (do not reuse).',
        'trust_level' => 50,
        'latency' => '24ms_STABLE',
        'avatar_url' => null,
        'is_ai' => false,
        'email_verified_at' => null,
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $legacy = User::find(1);
    $limiter = app(LlmRateLimiter::class);

    expect($limiter->isDemo($legacy))->toBeFalse()
                ->and($limiter->isDemo(makeDemoUser()))->toBeTrue();
});

it('returns 429 with Retry-After and writes nothing when the interactive budget is exhausted', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([MessageReceived::class]);

    $demo = makeDemoUser();
    $bot  = makeBotUser(['name' => 'SENTINEL_01']);

    // Seed a real, replyable assistant message owned by $demo.
    $msgId = app(SendMessageAction::class)->execute(
        $bot->id, $demo->id, 'Welcome.', 'SENTINEL_01', 'assistant'
    );

    $limiter = app(LlmRateLimiter::class);
    $limiter->consume(LlmRateLimiter::INTERACTIVE, $demo); // 1
    $limiter->consume(LlmRateLimiter::INTERACTIVE, $demo); // 2
    $limiter->consume(LlmRateLimiter::INTERACTIVE, $demo); // 3 — demo cap
    expect($limiter->attempts(LlmRateLimiter::INTERACTIVE, $demo))->toBe(3);

        $before = DB::table('agent_conversation_messages')->count();

    $this->actingAs($demo)
        ->postJson('/messages', ['message_id' => $msgId, 'text' => 'hi'])
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertHeader('X-RateLimit-Limit')
        ->assertHeader('X-RateLimit-Remaining')
        ->assertJson(['message' => 'AI_RATE_LIMITED']);

    expect($limiter->attempts(LlmRateLimiter::INTERACTIVE, $demo))->toBe(3) // not consumed again
        ->and(DB::table('agent_conversation_messages')->count())->toBe($before) // no writes on 429
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(0);    // no job dispatched
});

it('stores the human message and dispatches the agent job when under the cap', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([MessageReceived::class]);

    $demo = makeDemoUser();
    $bot  = makeBotUser(['name' => 'SENTINEL_01']);

    $msgId = app(SendMessageAction::class)->execute(
        $bot->id, $demo->id, 'Welcome.', 'SENTINEL_01', 'assistant'
    );
    $convId = DB::table('agent_conversations')->where('user_id', $demo->id)->value('id');

    $limiter = app(LlmRateLimiter::class);
    $before  = DB::table('agent_conversation_messages')->count();

        $this->actingAs($demo)
        ->postJson('/messages', ['message_id' => $msgId, 'text' => 'hi there'])
        ->assertOk();

    expect($limiter->attempts(LlmRateLimiter::INTERACTIVE, $demo))->toBe(1)
        ->and(DB::table('agent_conversation_messages')->count())->toBe($before + 1) // human msg only
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);
});

it('does not double-count: exactly one success then 429 until the window resets', function (): void {
    Bus::fake([HandleAgentResponse::class]);
    Event::fake([MessageReceived::class]);

    $demo = makeDemoUser();
    $bot  = makeBotUser(['name' => 'SENTINEL_01']);

    $msgId = app(SendMessageAction::class)->execute(
        $bot->id, $demo->id, 'Welcome.', 'SENTINEL_01', 'assistant'
    );

    $limiter = app(LlmRateLimiter::class);
    $limiter->consume(LlmRateLimiter::INTERACTIVE, $demo); // 1 used
    $limiter->consume(LlmRateLimiter::INTERACTIVE, $demo); // 2 used (1 token left)

    $codes = [];
    foreach (['a', 'b', 'c'] as $suffix) {
                $codes[] = $this->actingAs($demo)
            ->postJson('/messages', ['message_id' => $msgId, 'text' => "msg {$suffix}"])
            ->status();
    }

    // 1st granted (consume -> 3), 2nd + 3rd blocked (cap reached).
    expect($codes)->toMatchArray([200, 429, 429])
        ->and($limiter->attempts(LlmRateLimiter::INTERACTIVE, $demo))->toBe(3)
        ->and(Bus::dispatched(HandleAgentResponse::class))->toHaveCount(1);
});

it('gates startup greetings: an exhausted STARTUP budget blocks the job with no DB write', function (): void {
    $demo = makeDemoUser();
    $bot  = makeBotUser(['name' => 'SENTINEL_01']);

    $limiter = app(LlmRateLimiter::class);
    $limiter->consume(LlmRateLimiter::STARTUP, $demo); // 1
    $limiter->consume(LlmRateLimiter::STARTUP, $demo); // 2 — demo startup cap

    $before = DB::table('agent_conversation_messages')->count();

    $job = new AutoSendAgentMessage($demo->id, 'SENTINEL_01');
    app()->call($job->handle(...)); // resolves the real LlmRateLimiter from the container

    expect($limiter->attempts(LlmRateLimiter::STARTUP, $demo))->toBe(2) // blocked, no extra consume
        ->and(DB::table('agent_conversation_messages')->count())->toBe($before); // no greeting written
});