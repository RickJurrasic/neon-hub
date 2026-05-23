<?php

use App\Events\SystemAlertTriggered;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('test route successfully dispatches websocket event to reverb', function () {
    Event::fake();

    // 1. Vytvoříme a přihlásíme uživatele
    $user = User::factory()->create();
    $this->actingAs($user);

    // 2. Zavoláme routu
    $response = $this->get('/test-signal');

    // 3. Assert
    $response->assertStatus(200);

    // 4. Ověříme, že event byl dispatchován pro správného uživatele
    Event::assertDispatched(SystemAlertTriggered::class, function ($event) use ($user) {
        return $event->userId === $user->id && $event->message === 'Krizový stav aktivován!';
    });
});
