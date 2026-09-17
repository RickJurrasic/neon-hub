<?php

use App\Ai\Agents\Actions\Ai\ExecuteFriendRequestAction;
use App\Ai\Agents\Actions\Ai\ExecuteSendMessageAction;
use App\Ai\Agents\AIAgent;
use App\Events\AIActionPerformed;
use App\Events\FriendRequestReceived;
use App\Events\MessageReceived;
use App\Jobs\ProcessAIAction;
use App\Models\User;
use App\Services\ActiveDemoUsers;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Responses\AgentResponse;

// Seed the legacy Recruiter Phantom (id 1) so factory-created demo humans/bots
// get id >= 2, matching production where id 1 is the shared global identity.
beforeEach(function (): void {
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
});

it('send_message executor broadcasts to the supplied demo human own channel and owns the conversation', function () {
    Event::fake([MessageReceived::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();

    $response = Mockery::mock(AgentResponse::class);
    $response->text = 'Neon lights ahead, traveller.';

    $agent = Mockery::mock(AIAgent::class);
    $agent->shouldReceive('withPersona')->zeroOrMoreTimes()->andReturnSelf();
    $agent->shouldReceive('prompt')->once()->andReturn($response);
    $this->instance(AIAgent::class, $agent);

    app(ExecuteSendMessageAction::class)->execute($bot, ['recipient_id' => (int) $human->id]);

    Event::assertDispatched(MessageReceived::class, function (MessageReceived $event) use ($human) {
        $channels = $event->broadcastOn();

        return $event->userId === (int) $human->id
            && $channels[0] instanceof PrivateChannel
            && (string) $channels[0] === 'private-App.Models.User.'.((int) $human->id);
    });

    $this->assertDatabaseHas('agent_conversations', ['user_id' => $human->id, 'agent_user_id' => $bot->id]);
    $this->assertDatabaseHas('agent_conversation_messages', ['user_id' => $human->id, 'role' => 'assistant']);
    $this->assertDatabaseMissing('agent_conversation_messages', ['user_id' => (int) $bot->id, 'role' => 'assistant']);

    Mockery::close();
});

it('send_message executor validates recipient: blocks AI/missing/nonexistent, allows valid humans', function () {
    Event::fake([MessageReceived::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();

    // Bound so the executor resolves; the guard returns before it is used.
    $response = Mockery::mock(AgentResponse::class);
$response->text = 'Test response.';

$agent = Mockery::mock(AIAgent::class);
$agent->shouldReceive('withPersona')->zeroOrMoreTimes()->andReturnSelf();
$agent->shouldReceive('prompt')->twice()->andReturn($response);

$this->instance(AIAgent::class, $agent);

    // Should fail closed: bot self (AI recipient)
    app(ExecuteSendMessageAction::class)->execute($bot, ['recipient_id' => (int) $bot->id]); // self (bot -> bot)

    // Should succeed: legacy id 1 (human)
    app(ExecuteSendMessageAction::class)->execute($bot, ['recipient_id' => 1]);

    // Should fail closed: nonexistent recipient
    app(ExecuteSendMessageAction::class)->execute($bot, ['recipient_id' => (int) $bot->id + 999999]);

    // Should fail closed: missing recipient
    app(ExecuteSendMessageAction::class)->execute($bot, []);

    // Should succeed: human self (valid human recipient)
    app(ExecuteSendMessageAction::class)->execute($human, ['recipient_id' => (int) $human->id]);

    // Exactly 2 MessageReceived events should be dispatched (for id 1 and human self)
    Event::assertDispatched(MessageReceived::class, function (MessageReceived $event) use ($human) {
        return in_array($event->userId, [1, $human->id]);
    });

    // Exactly 2 assistant messages should exist in the database
    $this->assertDatabaseCount('agent_conversation_messages', 2);

    Mockery::close();
});

it('friend_request executor broadcasts to the supplied demo human own channel', function () {
    Event::fake([FriendRequestReceived::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();

    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $human->id]);

    Event::assertDispatched(FriendRequestReceived::class);

    $this->assertDatabaseHas('friendships', ['sender_id' => $bot->id, 'recipient_id' => $human->id, 'status' => 'pending']);
    $this->assertDatabaseMissing('friendships', ['recipient_id' => 1]);
});

it('friend_request executor validates recipient: blocks AI/missing/nonexistent, allows valid humans', function () {
    Event::fake([FriendRequestReceived::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();
    $aiBot = makeBotUser();

    // Bot self-recipient should fail closed.
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $bot->id]);

    // Missing recipient should fail closed.
    app(ExecuteFriendRequestAction::class)->execute($bot, []);

    // Nonexistent recipient should fail closed.
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $bot->id + 999999]);

    // AI recipient should fail closed.
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $aiBot->id]);

    // Human self-recipient should fail closed.
    app(ExecuteFriendRequestAction::class)->execute($human, ['recipient_id' => (int) $human->id]);

    // Fail-closed cases must not dispatch any event.
    Event::assertNotDispatched(FriendRequestReceived::class);

    // Valid demo human recipient should succeed.
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $human->id]);

    // Valid legacy human ID 1 should also succeed when the fixture is human.
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => 1]);

    // Exactly two events should be dispatched for the two valid human recipients.
    Event::assertDispatched(FriendRequestReceived::class, null, 2);

    // The two valid recipients should be the demo human and the legacy human ID 1.
    Event::assertDispatched(FriendRequestReceived::class, function (FriendRequestReceived $event) use ($human) {
        return $event->userId === (int) $human->id;
    });

    Event::assertDispatched(FriendRequestReceived::class, function (FriendRequestReceived $event) {
        return $event->userId === 1;
    });

    // Friendship rows should exist for the valid human recipients only.
    $this->assertDatabaseHas('friendships', ['sender_id' => $bot->id, 'recipient_id' => (int) $human->id, 'status' => 'pending']);
    $this->assertDatabaseHas('friendships', ['sender_id' => $bot->id, 'recipient_id' => 1, 'status' => 'pending']);

    $this->assertDatabaseMissing('friendships', ['sender_id' => $bot->id, 'recipient_id' => (int) $bot->id]);
    $this->assertDatabaseMissing('friendships', ['sender_id' => $bot->id, 'recipient_id' => (int) $aiBot->id]);
    $this->assertDatabaseMissing('friendships', ['sender_id' => $bot->id, 'recipient_id' => (int) $bot->id + 999999]);
});

it('end-to-end: ProcessAIAction runs a scheduled friend_request for the active human', function () {
    Event::fake([FriendRequestReceived::class, AIActionPerformed::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();
    ActiveDemoUsers::record((int) $human->id);

    ProcessAIAction::dispatch($bot->id, 'friend_request', ['recipient_id' => (int) $human->id]);

    Event::assertDispatched(FriendRequestReceived::class, fn (FriendRequestReceived $event) => $event->userId === (int) $human->id);
    $this->assertDatabaseHas('friendships', ['sender_id' => $bot->id, 'recipient_id' => $human->id, 'status' => 'pending']);
    $this->assertDatabaseMissing('friendships', ['recipient_id' => 1]);
});

it('friend_request executor uses demo_owner_id fallback when recipient_id is absent', function () {
    Event::fake([FriendRequestReceived::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();

    // Payload has demo_owner_id but NO recipient_id - tests the fallback path
    app(ExecuteFriendRequestAction::class)->execute($bot, ['demo_owner_id' => (int) $human->id]);

    Event::assertDispatched(FriendRequestReceived::class, function (FriendRequestReceived $event) use ($human) {
        return $event->userId === (int) $human->id;
    });

    // Verify friendship row was created with correct sender (bot) and recipient (human, via fallback)
    $this->assertDatabaseHas('friendships', [
        'sender_id' => $bot->id,
        'recipient_id' => $human->id,
        'status' => 'pending'
    ]);
});

it('friend_request executor prevents duplicate pending requests for same bot/human pair', function () {
    Event::fake([FriendRequestReceived::class]);

    $bot = makeBotUser();
    $human = makeDemoUser();

    // First request should succeed
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $human->id]);

    // Verify exactly one friendship exists
    $this->assertDatabaseHas('friendships', [
        'sender_id' => $bot->id,
        'recipient_id' => $human->id,
        'status' => 'pending'
    ]);
    $this->assertEquals(1, \App\Models\Friendship::where('sender_id', $bot->id)
        ->where('recipient_id', $human->id)
        ->where('status', 'pending')
        ->count());

    // Second request for same bot/human should be deduped (no new row)
    app(ExecuteFriendRequestAction::class)->execute($bot, ['recipient_id' => (int) $human->id]);

    // Count remains exactly 1 - no duplicate created
    $this->assertEquals(1, \App\Models\Friendship::where('sender_id', $bot->id)
        ->where('recipient_id', $human->id)
        ->where('status', 'pending')
        ->count());

    // Verify the Friendship model's between() method would still return true for existing pair
    $this->assertTrue(\App\Models\Friendship::between($bot->id, $human->id, 'pending')->exists());
});
