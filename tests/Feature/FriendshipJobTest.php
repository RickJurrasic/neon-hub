<?php

use App\Actions\SendFriendRequestAction;
use App\Events\FriendRequestReceived;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// 1. UNIT TEST: Ověříme, že action v sobě drží správná data
it('holds the correct user and bot data', function (): void {
    $user = User::factory()->create();
    $botId = User::factory()->create(['name' => 'SENTINEL_01'])->id;

    $action = new SendFriendRequestAction();

    $result = $action->execute($user->id, $botId);

    expect($result)->not->toBeNull()
        ->and($result->sender_id)->toBe($user->id)
        ->and($result->recipient_id)->toBe($botId);
});

// 2. FEATURE TEST: Ověříme, že celý řetězec z frontendu funguje
it('dispatches the friend request action when system is initialized via route', function (): void {
    Event::fake();

    $user = User::factory()->create();
    $bot = User::factory()->create(['name' => 'SENTINEL_01']);

    $this->actingAs($user)
        ->post(route('system.initialize'))
        ->assertStatus(200);

    // Ověříme, že byl vyvolán event
    Event::assertDispatched(FriendRequestReceived::class);
});

// 3. REGRESSION: prevents same-direction duplicate via app-level check
it('returns null when a same-direction friendship already exists', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $action = new SendFriendRequestAction();

    $first = $action->execute($sender->id, $recipient->id);
    expect($first)->not->toBeNull();

    $second = $action->execute($sender->id, $recipient->id);
    expect($second)->toBeNull();

    expect(Friendship::where('sender_id', $sender->id)
        ->where('recipient_id', $recipient->id)
        ->count())->toBe(1);
});

// 4. REGRESSION: prevents cross-direction duplicate via between() scope
it('returns null when a cross-direction friendship already exists', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $action = new SendFriendRequestAction();

    $first = $action->execute($sender->id, $recipient->id);
    expect($first)->not->toBeNull();

    $second = $action->execute($recipient->id, $sender->id);
    expect($second)->toBeNull();

    expect(Friendship::count())->toBe(1);
});

// 5. REGRESSION: DB unique constraint catches TOCTOU race condition
it('catches a duplicate insert via the DB unique constraint', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    Friendship::create([
        'sender_id' => $sender->id,
        'recipient_id' => $recipient->id,
        'status' => 'pending',
    ]);

    $caught = false;
    try {
        DB::table('friendships')->insert([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    } catch (QueryException $e) {
        $caught = true;
    }

    expect($caught)->toBeTrue();
    expect(Friendship::count())->toBe(1);
});
