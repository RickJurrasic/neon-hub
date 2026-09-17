<?php

use App\Actions\SendMessageAction;
use App\Events\MessageReceived;
use Illuminate\Support\Facades\Event;

it('returns the authenticated user message stream without crashing', function (): void {
    // Fake the ShouldBroadcast event so a null broadcast driver cannot
    // interfere with this read-only GET, and to prevent any auto-reply
    // listener from adding extra messages to the seeded stream.
    Event::fake([MessageReceived::class]);

    $demo = makeDemoUser();
    $bot = makeBotUser(['name' => 'SENTINEL_01']);

    // Seed a human-owned conversation + assistant greeting via the same
    // action path the engine uses at runtime.
    app(SendMessageAction::class)->execute(
        (int) $bot->id,
        (int) $demo->id,
        'Telemetry green on deck, traveller.',
        'SENTINEL_01',
        'assistant',
    );

    // Ownership gate: both the conversation and the message belong to demo.
    $this->assertDatabaseHas('agent_conversations', ['user_id' => $demo->id]);
    $this->assertDatabaseHas('agent_conversation_messages', ['user_id' => $demo->id]);

    $response = $this->actingAs($demo)->getJson(route('messages.index'));

    // 1) Endpoint returns successfully (an Undefined-property crash would 500).
    $response->assertStatus(200);

    // 2) Shape matches what NeonMessages.vue expects from fetchMessages().
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'conversation_id',
                'agent',
                'agent_name',
                'text',
                'sender',
                'time',
                'created_at',
                'read',
                'role',
            ],
        ],
    ]);

    $body = $response->json();

    // The returned item must be a MESSAGE (content aliased to text), not a
    // conversation row: exactly one item, with the seeded body + role.
    expect($body['data'])->toBeArray()->toHaveCount(1);
    expect($body['data'][0]['conversation_id'])->not->toBeNull();
    expect($body['data'][0]['role'])->toBe('assistant');
    expect(str_contains((string) $body['data'][0]['text'], 'traveller'))->toBe(true);

    // `time` comes from Carbon::toTimeString() on the normalized created_at;
    // a raw string would crash "... ?->toTimeString()".
    expect($response->json('data.0.time'))->toMatch('/^\d{2}:\d{2}:\d{2}$/');
});