<?php

use App\Actions\SendFriendRequestAction;
use App\Events\FriendRequestReceived;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

// 1. UNIT TEST: Ověříme, že action v sobě drží správná data
it('holds the correct user and bot data', function () {
    $user = User::factory()->create();
    $botId = User::factory()->create(['name' => 'SENTINEL_01'])->id;

    $action = new SendFriendRequestAction;

    // Execute the action
    $result = $action->execute($user->id, $botId);

    // Verify friendship was created
    expect($result)->not->toBeNull()
        ->and($result->sender_id)->toBe($user->id)
        ->and($result->recipient_id)->toBe($botId);
});

// 2. FEATURE TEST: Ověříme, že celý řetězec z frontendu funguje
it('dispatches the friend request action when system is initialized via route', function () {
    Event::fake();

    $user = User::factory()->create();
    // Vytvoříme bota
    $bot = User::factory()->create(['name' => 'SENTINEL_01']);

    $this->actingAs($user)
        ->post(route('system.initialize'))
        ->assertStatus(200);

    // Ověříme, že byl vyvolán event
    Event::assertDispatched(FriendRequestReceived::class);
});
