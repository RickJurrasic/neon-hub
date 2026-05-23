<?php

use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('system dispatches message event to the correct receiver', function () {
    Event::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get('/test-message')->assertStatus(200);

    Event::assertDispatched(MessageReceived::class, function ($event) use ($user) {
        // Tady je ta oprava: $event->userId odpovídá tvému $this->userId
        return $event->userId === $user->id &&
               isset($event->data['text']); // Ověříme, že data tam jsou
    });
});
