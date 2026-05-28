<?php

use App\Jobs\SendFriendRequest;
use App\Models\User;

test('system dispatches friend request job', function () {
    Queue::fake(); // Faking fronty

    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/test-friend')->assertStatus(200);

    // Ověříme, že byl job odeslán do fronty
    Queue::assertPushed(SendFriendRequest::class);
});
