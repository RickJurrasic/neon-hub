<?php

use App\Jobs\SendFriendRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

// 1. UNIT TEST: Ověříme, že job v sobě drží správná data
it('holds the correct user and bot data', function () {
    $user = User::factory()->create();
    $botName = 'SENTINEL_01';

    $job = new SendFriendRequest($user->id, $botName);

    expect($job->userId)->toBe($user->id)
        ->and($job->botName)->toBe($botName);
});

// 2. FEATURE TEST: Ověříme, že celý řetězec z frontendu funguje (spouští job)
it('dispatches the SendFriendRequest job when system is initialized via route', function () {
    Queue::fake();

    $user = User::factory()->create();
    // Vytvoříme bota, aby job neměl problém v 'handle' (i když Queue::fake() kód v handle nespouští, je dobré mít data)
    User::factory()->create(['name' => 'SENTINEL_01']);

    $this->actingAs($user)
        ->post(route('system.initialize'))
        ->assertStatus(200);

    // Ověříme, že job byl poslán do fronty
    Queue::assertPushed(SendFriendRequest::class, function ($job) use ($user) {
        return $job->userId === $user->id && $job->botName === 'SENTINEL_01';
    });
});
