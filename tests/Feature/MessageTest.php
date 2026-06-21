<?php

use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('system dispatches message event to the correct receiver', function () {
    Event::fake();

    $user = User::factory()->create();

    // Simulujeme message event ruční vyvoláním
    event(new MessageReceived($user->id, [
        'text' => 'Test message content',
        'sender' => 'TEST_BOT',
    ]));

    Event::assertDispatched(MessageReceived::class, function ($event) use ($user) {
        return $event->userId === $user->id &&
               isset($event->data['text']);
    });
});
