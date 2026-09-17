<?php

use App\Ai\Agents\AIActionScheduler;
use App\Ai\Agents\AIAgent;
use App\Events\CommentCreated;
use App\Events\PostCreated;
use App\Events\PostLiked;
use App\Jobs\ProcessAIAction;
use App\Models\Post;
use App\Models\User;
use App\Services\ActiveDemoUsers;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

// Seed ID 1 as a legacy human (not demo, not bot) so factory-created users get id >= 2.
// This IS a human but does not match 'demo-%' pattern, so won't appear in activeIds().
beforeEach(function (): void {
    User::query()->insert([
        'id' => 1,
        'name' => 'Legacy Human',
        'email' => 'legacy@neonhub.io',
        'handle' => 'legacy',
        'role' => 'EXTERNAL_NODE',
        'trust_level' => 50,
        'latency' => '24ms_STABLE',
        'is_ai' => false,
        'email_verified_at' => null,
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

if (! function_exists('ns_seed_sched_next_past')) {
    /** Force a session scheduler timer to be already elapsed (eligible now). */
    function ns_seed_sched_next_past(int $id): void
    {
        Cache::put(
            ActiveDemoUsers::SCHED_NEXT_KEY.':'.$id,
            now()->subSecond()->timestamp,
            now()->addMinutes(5),
        );
    }
}

if (! function_exists('ns_seed_sched_next_future')) {
    /** Force a session scheduler timer into the future (not yet eligible). */
    function ns_seed_sched_next_future(int $id): void
    {
        Cache::put(
            ActiveDemoUsers::SCHED_NEXT_KEY.':'.$id,
            now()->addHours(1)->timestamp,
            now()->addHours(1),
        );
    }
}

if (! function_exists('ns_actor_is_shared_ai_bot')) {
    /** Assert a pushed job was performed by a globally-shared AI bot actor. */
    function ns_actor_is_shared_ai_bot(ProcessAIAction $job): bool
    {
        $actor = User::find($job->userId);

        return $actor !== null && $actor->is_ai === true;
    }
}

it('dispatches a single send_message to the active session own human only', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);

    expect($pushed)->toHaveCount(1);

    $job = $pushed->first();
    expect($job->actionType)->toBe('send_message')
        ->and($job->payload['recipient_id'])->toBe($human->id)
        ->and($job->userId)->not->toBe($human->id)
        ->and(ns_actor_is_shared_ai_bot($job))->toBeTrue();
});

it('dispatches a single friend_request to the active session own human only', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);

    config()->set('ai_actions.actions', ['friend_request' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);

    expect($pushed)->toHaveCount(1);

    $job = $pushed->first();
    expect($job->actionType)->toBe('friend_request')
        ->and($job->payload['recipient_id'])->toBe($human->id)
        ->and($job->userId)->not->toBe($human->id)
        ->and(ns_actor_is_shared_ai_bot($job))->toBeTrue();
});

it('dispatches one action per session and never cross-routes between sessions', function () {
    $a = makeDemoUser();
    $b = makeDemoUser();
    makeBotUser();
    makeBotUser(); // second shared actor

    ActiveDemoUsers::record((int) $a->id);
    ActiveDemoUsers::record((int) $b->id);
    ns_seed_sched_next_past((int) $a->id);
    ns_seed_sched_next_past((int) $b->id);

    config()->set('ai_actions.actions', [
        'send_message' => ['description' => 'x'],
        'friend_request' => ['description' => 'x'],
    ]);
    Queue::fake();

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);

    expect($pushed)->toHaveCount(2);

    $recipients = $pushed->map(fn (ProcessAIAction $job) => (int) $job->payload['recipient_id'])
        ->sort()
        ->values()
        ->all();

    expect($recipients)->toBe([(int) $a->id, (int) $b->id]);

    // Session A is never targeted by session B's dispatch and vice-versa.
    expect($pushed->filter(fn (ProcessAIAction $job) => (int) $job->payload['recipient_id'] === (int) $a->id))->toHaveCount(1)
        ->and($pushed->filter(fn (ProcessAIAction $job) => (int) $job->payload['recipient_id'] === (int) $b->id))->toHaveCount(1);

    $humanIds = [(int) $a->id, (int) $b->id];
    foreach ($pushed as $job) {
        expect($job->payload)->toHaveKey('recipient_id')
            ->and(in_array((int) $job->payload['recipient_id'], $humanIds, true))->toBeTrue()
            ->and(ns_actor_is_shared_ai_bot($job))->toBeTrue();
    }
});

it('never dispatches a targeted action to user id 1', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    Queue::assertNotPushed(ProcessAIAction::class, function (ProcessAIAction $job) {
        return (int) ($job->payload['recipient_id'] ?? 0) === 1;
    });
});

it('never dispatches a targeted action to a bot or with a missing recipient', function () {
    $human = makeDemoUser();
    $bot = makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);

    config()->set('ai_actions.actions', ['friend_request' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);

    expect($pushed)->toHaveCount(1);
    $job = $pushed->first();

    expect($job->payload)->toHaveKey('recipient_id')
        ->and((int) $job->payload['recipient_id'])->not->toBe($bot->id)
        ->and((int) $job->payload['recipient_id'])->toBe($human->id)
        ->and(User::find((int) $job->payload['recipient_id'])->is_ai)->toBeFalse();
});

it('dispatches only for sessions whose timer elapsed, skipping not-yet-eligible sessions', function () {
    $a = makeDemoUser();
    $b = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $a->id);
    ActiveDemoUsers::record((int) $b->id);
    ns_seed_sched_next_past((int) $a->id);
    ns_seed_sched_next_future((int) $b->id);

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);

    expect($pushed)->toHaveCount(1)
        ->and((int) $pushed->first()->payload['recipient_id'])->toBe((int) $a->id);
});

it('advances each session on its own independent timer so A fires while B does not', function () {
    $a = makeDemoUser();
    $b = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $a->id);
    ActiveDemoUsers::record((int) $b->id);
    ns_seed_sched_next_past((int) $a->id);
    ns_seed_sched_next_future((int) $b->id);

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    // Tick 1: only A is eligible -> A fires, B is skipped on its own timer.
    AIActionScheduler::tick();
    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(1)
        ->and((int) Queue::pushed(ProcessAIAction::class)->last()->payload['recipient_id'])->toBe((int) $a->id);

    // A's timer is now advanced into the future; B still not eligible.
    // Queue::fake() is cumulative, so "no new dispatch" means the total stays 1.
    AIActionScheduler::tick();
    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(1);

    // Now only B becomes eligible on its OWN timer -> B fires independently.
    ns_seed_sched_next_past((int) $b->id);
    AIActionScheduler::tick();
    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(2)
        ->and((int) Queue::pushed(ProcessAIAction::class)->last()->payload['recipient_id'])->toBe((int) $b->id);
});

it('seeds a first deadline on first sighting without dispatching an action', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    // NOTE: no ns_seed_sched_next_past() -> first tick must lazily initialize.

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(0);
    $deadline = ActiveDemoUsers::nextActionAt((int) $human->id);
    expect($deadline)->not->toBeNull()
        ->and($deadline->greaterThan(now()))->toBeTrue();
});

it('does not dispatch for a session whose heartbeat has expired', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);
    // Heartbeat removed -> session no longer active, timer must NOT matter.
    Cache::forget(ActiveDemoUsers::ACTIVE_KEY.':'.(int) $human->id);

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(0);
});

it('propagates demo_owner_id through scheduler, executor, and PostCreated private channel', function () {
    $humanA = makeDemoUser();
    $humanB = makeDemoUser();
    $bot = makeBotUser();

    ActiveDemoUsers::record((int) $humanA->id);
    ActiveDemoUsers::record((int) $humanB->id);
    ns_seed_sched_next_past((int) $humanA->id);
    ns_seed_sched_next_past((int) $humanB->id);

    config()->set('ai_actions.actions', ['create_post' => ['description' => 'feed']]);
    Queue::fake();
    AIAgent::fake(['Post from scheduler A', 'Post from scheduler B']);

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);
    expect($pushed)->toHaveCount(2);

    $demoOwnerIds = $pushed->map(fn (ProcessAIAction $j) => (int) $j->payload['demo_owner_id'])
        ->sort()
        ->values()
        ->all();
    expect($demoOwnerIds)->toBe([(int) $humanA->id, (int) $humanB->id]);

    // Execute human A's job
    Event::fake([PostCreated::class]);
    $jobA = $pushed->first(fn (ProcessAIAction $j) => (int) $j->payload['demo_owner_id'] === (int) $humanA->id);
    $jobA->handle();

    // PostCreated routes to human A's private channel, NOT the bot's
    Event::assertDispatched(PostCreated::class, function (PostCreated $event) use ($humanA, $bot) {
        $channels = $event->broadcastOn();

        return $event->userId === (int) $humanA->id
            && $channels[0] instanceof PrivateChannel
            && $channels[0]->name === 'private-App.Models.User.'.$humanA->id
            && $channels[0]->name !== 'private-App.Models.User.'.$bot->id;
    });

    // Post persisted with correct ownership
    $post = Post::where('demo_owner_id', $humanA->id)->latest('id')->first();
    expect($post)->not->toBeNull()
        ->and((int) $post->demo_owner_id)->toBe((int) $humanA->id)
        ->and((int) $post->user_id)->toBe((int) $bot->id);
});

it('PostCreated routes to demo human channel and not to a different demo human channel', function () {
    $humanA = makeDemoUser();
    $humanB = makeDemoUser();

    $post = ['id' => 999, 'author' => 'Test', 'content' => 'Hello', 'type' => 'ai'];

    // Event for human A's session
    $eventA = new PostCreated($post, (int) $humanA->id);
    $channelsA = $eventA->broadcastOn();

    expect($channelsA[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channelsA[0]->name)->toBe('private-App.Models.User.'.$humanA->id);

    // Event for human B's session routes to a different channel
    $eventB = new PostCreated($post, (int) $humanB->id);
    $channelsB = $eventB->broadcastOn();

    expect($channelsB[0]->name)->toBe('private-App.Models.User.'.$humanB->id)
        ->and($channelsA[0]->name)->not->toBe($channelsB[0]->name);
});

it('routes PostLiked to the demo session owner private channel, not the bot', function () {
    $humanA = makeDemoUser();
    $humanB = makeDemoUser();
    $bot = makeBotUser();

    // Post owned by human A but authored by bot
    $event = new PostLiked(1, 5, $bot->id, $bot->name, true, (int) $humanA->id);
    $channels = $event->broadcastOn();

    expect($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-App.Models.User.'.$humanA->id)
        ->and($channels[0]->name)->not->toBe('private-App.Models.User.'.$bot->id);

    // Different session -> different channel
    $eventB = new PostLiked(2, 3, $bot->id, $bot->name, true, (int) $humanB->id);
    $channelsB = $eventB->broadcastOn();

    expect($channelsB[0]->name)->toBe('private-App.Models.User.'.$humanB->id)
        ->and($channels[0]->name)->not->toBe($channelsB[0]->name);
});

it('routes CommentCreated to the demo session owner private channel, not the bot', function () {
    $humanA = makeDemoUser();
    $humanB = makeDemoUser();
    $bot = makeBotUser();

    $commentA = [
        'id' => 1,
        'post_id' => 1,
        'content' => 'Nice post!',
        'author' => $bot->name,
        'demo_owner_id' => (int) $humanA->id,
    ];

    // Post owned by human A, bot writes a comment
    $event = new CommentCreated(1, $commentA, (int) $humanA->id, $bot->id);
    $channels = $event->broadcastOn();

    expect($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-App.Models.User.'.$humanA->id)
        ->and($channels[0]->name)->not->toBe('private-App.Models.User.'.$bot->id);

    // Different session -> different channel
    $commentB = [
        'id' => 2,
        'post_id' => 2,
        'content' => 'Nice post!',
        'author' => $bot->name,
        'demo_owner_id' => (int) $humanB->id,
    ];

    $eventB = new CommentCreated(2, $commentB, (int) $humanB->id, $bot->id);
    $channelsB = $eventB->broadcastOn();

    expect($channelsB[0]->name)->toBe('private-App.Models.User.'.$humanB->id)
        ->and($channels[0]->name)->not->toBe($channelsB[0]->name);
});

it('PostCreated falls back to post user_id channel when demo_owner_id is null (legacy shared post)', function () {
    $post = ['id' => 1, 'author' => 'SYSTEM', 'content' => 'Legacy shared post', 'type' => 'seed'];
    $authorId = 77;

    $event = new PostCreated($post, $authorId);
    $channels = $event->broadcastOn();

    expect($channels[0])->toBeInstanceOf(PrivateChannel::class)
        ->and($channels[0]->name)->toBe('private-App.Models.User.'.$authorId);
});

it('keeps public feed actions untargeted', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);

    config()->set('ai_actions.actions', ['create_post' => ['description' => 'feed']]);
    Queue::fake();

    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);

    expect($pushed)->toHaveCount(1);
    $job = $pushed->first();
    expect($job->actionType)->toBe('create_post')
        ->and($job->payload)->not->toHaveKey('recipient_id')
        ->and($job->payload['demo_owner_id'])->toBe($human->id);
});

it('fails closed when no global AI bot exists', function () {
    $human = makeDemoUser();

    // No bots exist -> aiUsers will be empty.
    ActiveDemoUsers::record((int) $human->id);
    ns_seed_sched_next_past((int) $human->id);

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(0);
    // Timer is NOT advanced when nothing was dispatched -> keeps retrying.
    expect(ActiveDemoUsers::nextActionAt((int) $human->id)->isPast())->toBeTrue();
});

it('dispatches nothing when no demo session is active', function () {
    makeDemoUser(); // present, never recorded -> inactive
    makeBotUser();

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    AIActionScheduler::tick();

    expect(Queue::pushed(ProcessAIAction::class))->toHaveCount(0);
    Queue::assertNotPushed(ProcessAIAction::class, function (ProcessAIAction $job) {
        return (int) ($job->payload['recipient_id'] ?? 0) === 1;
    });
});

it('handles stale __PHP_Incomplete_Class cache value gracefully', function () {
    $human = makeDemoUser();
    makeBotUser();

    ActiveDemoUsers::record((int) $human->id);

    // Reproduce the production failure: the database cache store runs with
    // serializable_classes => false, so a cached Carbon is read back as a
    // __PHP_Incomplete_Class. Plant that exact malformed value directly.
    $stale = unserialize(serialize(now()->subSecond()), ['allowed_classes' => false]);

    Cache::put(ActiveDemoUsers::SCHED_NEXT_KEY.':'.(int) $human->id, $stale, now()->addMinutes(5));

    config()->set('ai_actions.actions', ['send_message' => ['description' => 'demo']]);
    Queue::fake();

    // Must NOT throw "Object of class __PHP_Incomplete_Class could not be
    // converted to string"; the stale entry is discarded and re-seeded.
    AIActionScheduler::tick();

    // The scheduler re-seeded the session with a valid, future deadline.
    $deadline = ActiveDemoUsers::nextActionAt((int) $human->id);
    expect($deadline)->not->toBeNull();
    expect($deadline)->toBeInstanceOf(CarbonInterface::class);
    expect($deadline->greaterThan(now()))->toBeTrue();

    // Existing scheduler behaviour remains intact: a past deadline on the next
    // tick still dispatches a single send_message to this session's own human.
    ns_seed_sched_next_past((int) $human->id);
    AIActionScheduler::tick();

    $pushed = Queue::pushed(ProcessAIAction::class);
    expect($pushed)->toHaveCount(1);

    $job = $pushed->first();
    expect($job->actionType)->toBe('send_message')
        ->and($job->payload['recipient_id'])->toBe($human->id);
});
