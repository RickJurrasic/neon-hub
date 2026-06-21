<?php

use App\Events\SystemAlertTriggered;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('system alert event is dispatched with correct data', function () {
    // 1. Fakeujeme eventy
    Event::fake([SystemAlertTriggered::class]);

    $user = User::factory()->create();

    // 2. Vyvoláme event ručně
    event(new SystemAlertTriggered($user->id, 'Test alert succesful!'));

    // 3. Ověříme, že event byl dispatchován a má správná data
    Event::assertDispatched(SystemAlertTriggered::class, function ($event) use ($user) {
        return $event->userId === $user->id &&
               $event->message === 'Test alert succesful!';
    });

    // 4. Bonus: Ověříme, že event má správný kanál (broadcastOn)
    Event::assertDispatched(SystemAlertTriggered::class, function ($event) use ($user) {
        $channels = $event->broadcastOn();

        return count($channels) === 1 &&
               $channels[0]->name === 'private-App.Models.User.'.$user->id;
    });
});
