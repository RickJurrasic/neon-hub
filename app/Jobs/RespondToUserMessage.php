<?php

namespace App\Jobs;

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class RespondToUserMessage implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Počet pokusů o opakování jobu při selhání LLM API.
     */
    public int $tries = 3;

    /**
     * Proleva mezi opakovanými pokusy (v sekundách).
     */
    public int $backoff = 5;

    /**
     * Maximální doba běhu jobu (v sekundách).
     */
    public int $timeout = 30;

    public function __construct(
        public readonly int $userId,
        public readonly string $conversationId,
        public readonly string $agentName = 'SENTINEL_01'
    ) {}

    public function handle(AIAgent $agent): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            Log::warning("RespondToUserMessage skipped: User {$this->userId} not found.");

            return;
        }

        $aiResponse = $this->generateAiResponse($agent);

        if (empty($aiResponse)) {
            Log::warning("RespondToUserMessage aborted: Empty AI response generated for User {$this->userId}.");

            return;
        }

        $this->saveAndBroadcast($user->id, $aiResponse);
    }

    private function generateAiResponse(AIAgent $agent): string
    {
        $response = $agent
            ->withPersona($this->agentName)
            ->loadConversation($this->conversationId)
            ->prompt('Respond to the users message. Be friendly, under 20 words.', provider: ['groq']);

        return Str::of($response->text ?? '')
            ->trim()
            ->replace(['"', "'"], '')
            ->toString();
    }

    private function saveAndBroadcast(int $userId, string $aiResponse): void
    {
        $newMessageId = (string) Str::uuid();
        $now = now();

        $this->saveMessageToDatabase($newMessageId, $aiResponse, $userId, $now);
        $this->broadcastMessageEvent($newMessageId, $aiResponse, $userId, $now);

        Log::info("RespondToUserMessage: Response sent from agent [{$this->agentName}] to User [{$userId}]");
    }

    private function saveMessageToDatabase(string $id, string $content, int $userId, Carbon $now): void
    {
        DB::table('agent_conversation_messages')->insert([
            'id' => $id,
            'conversation_id' => $this->conversationId,
            'user_id' => $userId,
            'agent' => AIAgent::class,
            'role' => 'assistant',
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function broadcastMessageEvent(string $id, string $content, int $userId, Carbon $now): void
    {
        event(new MessageReceived($userId, [
            'id' => $id,
            'conversation_id' => $this->conversationId,
            'agent' => AIAgent::class,
            'agent_name' => $this->agentName,
            'sender' => $this->agentName,
            'text' => $content,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => false,
            'role' => 'assistant',
        ]));
    }

    /**
     * Ošetření trvalého selhání jobu.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("RespondToUserMessage failed permanently for User {$this->userId}: {$exception->getMessage()}");
    }
}
