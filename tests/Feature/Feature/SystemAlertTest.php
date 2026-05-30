<?php

use App\Events\SystemAlertTriggered;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('test route successfully dispatches websocket event to reverb', function () {
    // 1. Fakeujeme eventy i broadcasty
    Event::fake([SystemAlertTriggered::class]);

    $user = User::factory()->create();
    $this->actingAs($user);

    // 2. Zavoláme routu
    $response = $this->get('/test-signal');

    // 3. Assert status
    $response->assertStatus(200);

    // 4. Ověříme, že event byl dispatchován a má správná data
    Event::assertDispatched(SystemAlertTriggered::class, function ($event) use ($user) {
        return $event->userId === $user->id &&
               $event->message === 'Krizový stav aktivován!';
    });

    // 5. Bonus: Ověříme, že event má správný kanál (broadcastOn)
    Event::assertDispatched(SystemAlertTriggered::class, function ($event) use ($user) {
        $channels = $event->broadcastOn();

        return count($channels) === 1 &&
               $channels[0]->name === 'private-App.Models.User.'.$user->id;
    });
});
