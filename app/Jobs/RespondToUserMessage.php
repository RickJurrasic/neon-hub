<?php

namespace App\Jobs;

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RespondToUserMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $conversationId,
        public string $agentName = 'SENTINEL_01'
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $aiResponse = $this->generateAiResponse();

        $this->saveAndBroadcast($user->id, $aiResponse);
    }

    private function generateAiResponse(): string
    {
        return app(AIAgent::class)
            ->withPersona($this->agentName)
            ->loadConversation($this->conversationId)
            ->prompt('Respond to the users message. Be friendly, under 20 words.', provider: ['groq'])
            ->text;
    }

    private function saveAndBroadcast(int $userId, string $aiResponse): void
    {
        $newMessageId = (string) Str::uuid();
        $now = now();

        $this->saveMessageToDatabase($newMessageId, $aiResponse, $userId, $now);
        $this->broadcastMessageEvent($newMessageId, $aiResponse, $userId, $now);
    }

    private function saveMessageToDatabase(string $id, string $content, int $userId, $now): void
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

    private function broadcastMessageEvent(string $id, string $content, int $userId, $now): void
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
}
