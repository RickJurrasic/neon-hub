<?php

namespace Tests\Feature\Jobs;

use App\Ai\Agents\SentinelAgent;
use App\Events\MessageReceived;
use App\Jobs\RespondToUserMessage;
use App\Models\User; // Tuto třídu importuj
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Responses\AgentResponse;
use Mockery;

// Importuj správně takto

it('responds to user message, persists to database and dispatches event', function () {
    Event::fake([MessageReceived::class]);
    $user = User::factory()->create();
    $conversationId = 'conv_123';
    $aiMockResponse = 'Neural connection established.';

    // 1. Místo vytváření instance třídy ji prostě zamockujeme (to obejde konstruktor)
    $responseMock = Mockery::mock(AgentResponse::class);

    // 2. Nastavíme mocku, aby vracel náš text
    // Předpokládám, že tvůj kód přistupuje k výsledku přes $response->text
    $responseMock->text = $aiMockResponse;

    // 3. Vytvoříme mock SentinelAgenta
    $agentMock = Mockery::mock(SentinelAgent::class);
    $agentMock->shouldReceive('loadConversation')->andReturnSelf();

    // 4. Řekneme agentovi, aby vracel náš responseMock
    $agentMock->shouldReceive('prompt')->andReturn($responseMock);

    // 5. Vložíme mock do containeru
    $this->instance(SentinelAgent::class, $agentMock);

    // Act: Spustíme job
    $job = new RespondToUserMessage($user->id, $conversationId);
    $job->handle();

    // Assert: Ověříme výsledky
    $this->assertDatabaseHas('agent_conversation_messages', [
        'conversation_id' => $conversationId,
        'content' => $aiMockResponse,
    ]);

    Event::assertDispatched(MessageReceived::class, function ($event) use ($user, $aiMockResponse) {
        return $event->userId === $user->id && $event->data['text'] === $aiMockResponse;
    });
});
