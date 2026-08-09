<?php

namespace Tests\Feature\Jobs;

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Jobs\RespondToUserMessage;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Ai\Responses\AgentResponse;
use Mockery;

it('responds to user message, persists to database and dispatches event', function (): void {
    Event::fake([MessageReceived::class]);
    $user = User::factory()->create();
    $conversationId = 'conv_123';
    $aiMockResponse = 'Neural connection established.';

    // 1. Místo vytváření instance třídy ji prostě zamockujeme (to obejde konstruktor)
    $responseMock = Mockery::mock(AgentResponse::class);

    // 2. Nastavíme mocku, aby vracel náš text
    $responseMock->text = $aiMockResponse;

    // 3. Vytvoříme mock AIAgenta
    $agentMock = Mockery::mock(AIAgent::class);
    $agentMock->shouldReceive('withPersona')->andReturnSelf();
    $agentMock->shouldReceive('loadConversation')->andReturnSelf();

    // 4. Řekneme agentovi, aby vracel náš responseMock
    $agentMock->shouldReceive('prompt')->andReturn($responseMock);

    // 5. Vložíme mock do containeru
    $this->instance(AIAgent::class, $agentMock);

    // Act: Spustíme job přes kontejner, aby se správně injektoval AIAgent mock
    $job = new RespondToUserMessage($user->id, $conversationId);
    app()->call($job->handle(...));

    // Assert: Ověříme výsledky
    $this->assertDatabaseHas('agent_conversation_messages', [
        'conversation_id' => $conversationId,
        'content' => $aiMockResponse,
    ]);

    Event::assertDispatched(MessageReceived::class, fn($event) => $event->userId === $user->id && $event->data['text'] === $aiMockResponse);
});