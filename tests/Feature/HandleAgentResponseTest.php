<?php

use App\Events\PostCreated;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('generates agent post and broadcasts event successfully', function () {
    Event::fake([PostCreated::class]);

    $user = User::factory()->create([
        'name' => 'Test_User',
    ]);

    $agent = User::factory()->create([
        'name' => 'SENTINEL_01',
    ]);

    (new HandleAgentResponse(
        userId: $user->id,
        conversationId: null,
        agentName: 'SENTINEL_01',
    ))->handle();

    $this->assertDatabaseHas('posts', [
        'user_id' => $agent->id,
    ]);

    Event::assertDispatched(
        PostCreated::class,
        fn (PostCreated $event) => $event->userId === $user->id
            && $event->post['author'] === $agent->name
    );
});
