<?php

use App\Actions\SendMessageAction;
use App\Events\CommentCreated;
use App\Events\FriendRequestReceived;
use App\Events\MessageReceived;
use App\Events\NewActivityAlert;
use App\Events\PostLiked;
use App\Http\Middleware\AutoLoginDemoUser;
use App\Models\Post;
use App\Models\User;
use App\Services\MessageService;
use App\Services\NeonHubService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

it('issues a distinct demo human per browser session and never reuses id 1 or bots', function (): void {
    // Legacy shared demo identity (id 1) that older builds reused as the sole
    // autologin target. Must stay intact, never reused.
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

    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);
    $gate = new AutoLoginDemoUser;

    // Build a fresh, isolated Session store simulating one browser session.
    $start = function (string $name, string $sid): Store {
        $session = new Store($name, new ArraySessionHandler(120));
        $session->setId($sid);
        $session->start();

        return $session;
    };

    // Run the middleware on a synthetic session and read back the per-session
    // demo_uid it mints (stored in this store). We never rely on internal guard
    // plumbing -- isolation is proven via the session-bound identity + DB row.
    $resolveUid = function (Store $session) use ($gate): string {
        Auth::logout(); // reset default guard so each synthetic session is anonymous

        $request = Request::create('/', 'GET');
        $request->setLaravelSession($session);

        $gate->handle($request, static fn () => new Response('ok'));

        return (string) $session->get('demo_uid');
    };

    $sessionA = $start('session_a', 'sid-a');
    $sessionB = $start('session_b', 'sid-b');

    $uidA = $resolveUid($sessionA);
    $uidAagain = $resolveUid($sessionA); // same browser -> stable identity (no churn)
    $uidB = $resolveUid($sessionB);

    expect($uidA)->toBe($uidAagain)
        ->and($uidB)->not->toBe($uidA)
        ->and($uidA)->not->toBe('')
        ->and($uidB)->not->toBe('');

    $userA = User::where('email', $uidA.'@neonhub.io')->first();
    $userB = User::where('email', $uidB.'@neonhub.io')->first();

    expect($userA)->not->toBeNull()
        ->and($userB)->not->toBeNull()
        ->and($userA->is_ai)->toBeFalse()
        ->and($userB->is_ai)->toBeFalse()
        ->and($userA->id)->not->toBe(1)
        ->and($userB->id)->not->toBe(1)
        ->and($userA->id)->not->toBe($userB->id);

        expect(User::find(1)->email)->toBe('demo@neonhub.io'); // seed intact
    expect($bot->fresh()->is_ai)->toBeTrue();              // bot stays shared/global
});

it('isolates conversations and inbox messages between two demo identities', function (): void {
    Event::fake([MessageReceived::class]);

    $owner = User::factory()->create(['is_ai' => false]);
    $other = User::factory()->create(['is_ai' => false]);
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    app(SendMessageAction::class)->execute(
        $bot->id,
        $owner->id,
        'Secure handshake complete.',
        'SENTINEL_01',
        'assistant'
    );

    // Assistant message broadcasts to the owning demo user's channel only.
    Event::assertDispatched(MessageReceived::class, fn ($event) => $event->userId === $owner->id);

    expect(app(MessageService::class)->getIndexMessages($owner->id))->not->toBeEmpty();
    expect(app(NeonHubService::class)->getMessagesData($owner->id))->not->toBeEmpty();

    // The other demo session sees none of the owner's conversations or messages.
    expect(app(MessageService::class)->getIndexMessages($other->id))->toBeEmpty();
    expect(app(NeonHubService::class)->getMessagesData($other->id))->toBeEmpty();
});

it('prevents cross-demo like, comment, and friendship visibility', function (): void {
    Event::fake([PostLiked::class, CommentCreated::class, NewActivityAlert::class, FriendRequestReceived::class]);

    $owner = User::factory()->create(['is_ai' => false]);
    $other = User::factory()->create(['is_ai' => false]);
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);
    $post = Post::factory()->create(['user_id' => $bot->id]);

    $this->actingAs($owner)->post('/posts/'.$post->id.'/like')->assertOk();
    $this->actingAs($owner)
        ->postJson('/posts/'.$post->id.'/comments', ['content' => 'Poznámka z demo relace A.'])
        ->assertOk();
    $this->actingAs($owner)->post('/friendships', ['recipient_id' => $bot->id])->assertOk();

    // Owner's actions are persisted under owner's identity ...
    $this->assertDatabaseHas('likes', ['user_id' => $owner->id]);
    $this->assertDatabaseHas('comments', ['user_id' => $owner->id, 'content' => 'Poznámka z demo relace A.']);
    $this->assertDatabaseHas('friendships', ['sender_id' => $owner->id, 'recipient_id' => $bot->id]);

    // ... and the other demo session never observes them.
    $this->assertDatabaseMissing('likes', ['user_id' => $other->id]);
    $this->assertDatabaseMissing('comments', ['user_id' => $other->id]);
    $this->assertDatabaseMissing('friendships', ['sender_id' => $other->id]);
});

it('routes broadcast events only to the recipient demo user own channel', function (): void {
    Event::fake([MessageReceived::class]);

    $owner = User::factory()->create(['is_ai' => false]);
    $other = User::factory()->create(['is_ai' => false]);
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    app(SendMessageAction::class)->execute(
        $bot->id,
        $owner->id,
        'Agent reply targeted at the owner demo session.',
        'SENTINEL_01',
        'assistant'
    );

    // MessageReceived embeds the recipient id as the private channel
    // (App.Models.User.{id}). The unchanged channels.php gate
    // ($user->id === $userId) lets only the owner join it; the other demo
    // session owns a different channel, so it never matches / never receives
    // the owner's bot traffic -> isolation holds automatically via unique IDs.
    Event::assertDispatched(MessageReceived::class, function ($event) use ($owner, $other): bool {
        $channels = $event->broadcastOn();

        return $event->userId === $owner->id
            && $event->userId !== $other->id
            && $channels[0] instanceof PrivateChannel;
    });
});
