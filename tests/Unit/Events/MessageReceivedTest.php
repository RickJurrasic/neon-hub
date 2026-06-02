<?php

use App\Events\MessageReceived;

it('broadcasts the correct payload structure', function () {
    $userId = 1;
    $data = [
        'id' => 'test-uuid',
        'conversation_id' => 'conv_abc',
        'text' => 'Testing neural response',
        'sender' => 'Sentinel',
    ];

    $event = new MessageReceived($userId, $data);

    // Testujeme broadcastWith metodu
    $payload = $event->broadcastWith();

    expect($payload['data'])->toBe($data)
        ->and($payload['data']['text'])->toBe('Testing neural response')
        ->and($payload['data']['sender'])->toBe('Sentinel');
});

it('broadcasts on the correct private channel', function () {
    $userId = 99;
    $event = new MessageReceived($userId, []);

    $channels = $event->broadcastOn();

    // Přidali jsme "private-" k očekávanému řetězci
    expect((string) $channels[0])->toBe('private-App.Models.User.99');
});
