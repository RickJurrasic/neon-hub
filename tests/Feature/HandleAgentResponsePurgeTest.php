<?php

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Responses\AgentResponse;

// Regression for the purge guard added to HandleAgentResponse::handle().
//
// Failure mode: the agent is resolved (conversation exists at the top of
// handle()), but the human purges the conversation during the slow LLM call,
// right before saveAndBroadcastMessage() persists + broadcasts. The guard
// re-checks existence before the write so no orphaned assistant message is
// stored and no stale MessageReceived reaches a purged channel.
it('does not write or broadcast when its conversation is purged mid-response', function (): void {
    Event::fake([MessageReceived::class]);
    Log::spy();

    $human = User::factory()->create();
    $bot = User::factory()->create(['name' => 'SENTINEL_01', 'is_ai' => true]);

    $conversationId = (string) Str::uuid();
    DB::table('agent_conversations')->insert([
        'id' => $conversationId,
        'user_id' => $human->id,
        'agent_user_id' => $bot->id,
        'title' => 'SECURE_CHANNEL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // A human message so isLastMessageFromAssistant() === false; otherwise the
    // job bails on the assistant-bail and never reaches the purge guard.
    DB::table('agent_conversation_messages')->insert([
        'id' => (string) Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $human->id,
        'agent' => 'App\\Ai\\Agents\\SentinelAgent',
        'role' => 'user',
        'content' => 'Hello, agent.',
        'attachments' => '[]',
        'tool_calls' => '[]',
        'tool_results' => '[]',
        'usage' => '[]',
        'meta' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Fake the LLM agent. When prompt() is invoked (during generateChatResponse)
    // we simulate the human purging the conversation — the exact race the guard
    // covers — then yield a response that would otherwise be persisted.
    $agentInstance = Mockery::mock(AIAgent::class);
    $agentInstance->shouldIgnoreMissing();
    $agentInstance->shouldReceive('prompt')
        ->zeroOrMoreTimes()
        ->andReturnUsing(function (...$args) use ($conversationId): AgentResponse {
            DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->delete();
            DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->delete();

            $response = Mockery::mock(AgentResponse::class);
            $response->text = 'Hello back, human.';

            return $response;
        });

    $agent = Mockery::mock(AIAgent::class);
    $agent->shouldReceive('withPersona')
        ->zeroOrMoreTimes()
        ->andReturn($agentInstance);

    (new HandleAgentResponse($human->id, $conversationId, null))->handle($agent);

        // No stale broadcast to the purged channel, and no orphaned assistant
    // message is persisted. (Both fail without the guard: the LLM response
    // that would otherwise be written is suppressed by the purge guard.)
    Event::assertNotDispatched(MessageReceived::class);

    // No orphaned assistant message persisted for the purged conversation.
    $this->assertDatabaseMissing('agent_conversation_messages', [
        'conversation_id' => $conversationId,
        'role' => 'assistant',
    ]);

    // The conversation was really purged mid-call (guard had a real deletion).
    $this->assertDatabaseMissing('agent_conversations', ['id' => $conversationId]);

    Mockery::close();
});
