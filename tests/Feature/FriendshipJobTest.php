<?php

use App\Jobs\SendFriendRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('friend request job holds correct data', function () {
    // Vytvoříme uživatele, aby job měl s čím pracovat
    $user = User::factory()->create();
    $botName = 'SENTINEL_01';

    // Vytvoříme instanci jobu s názvy proměnných, které skutečně máš
    $job = new SendFriendRequest($user->id, $botName);

    // Ověříme, že job má v sobě správná data
    expect($job->userId)->toBe($user->id)
        ->and($job->botName)->toBe($botName);
});
