<?php

use App\Events\FriendRequestReceived;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('system dispatches friend request event to the recipient', function () {
    Event::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    // Voláme tvou testovací routu z web.php
    $this->get('/test-friend')
        ->assertStatus(200);

    // Ověřujeme, že se event dispatchnul
    Event::assertDispatched(FriendRequestReceived::class, function ($event) {
        // Tady záleží, jak máš definovaný konstruktor v FriendRequestReceived
        // Podle routy '/test-friend' tam posíláš auth()->id() a pole dat.
        // Uprav si tyto podmínky podle toho, co reálně v eventu máš.
        return ! empty($event->data);
    });
});
