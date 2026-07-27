<?php

namespace App\Jobs;

use App\Ai\Agents\SentinelAgent;
use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AutoSendAgentMessage implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $agentName = 'SENTINEL_01'
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $bot = User::where('name', $this->agentName)->first();

        if (! $user || ! $bot) {
            return;
        }

        $conversationId = $this->ensureConversationId($user->id);
        $aiResponse = $this->generateGreeting($user->name);

        $this->saveAndBroadcast($user->id, $conversationId, $aiResponse);
    }

    private function generateGreeting(string $userName): string
    {
        return SentinelAgent::make()->prompt(
            "The user {$userName} just joined the NeonHub. Write a very short (max 15 words), terse, technical greeting.",
            provider: ['gemini', 'gemini_fallback', 'groq']
        )->text;
    }

    private function ensureConversationId(int $userId): string
    {
        $id = DB::table('agent_conversations')->where('user_id', $userId)->value('id');
        if ($id) {
            return $id;
        }

        $newId = (string) Str::uuid();
        DB::table('agent_conversations')->insert([
            'id' => $newId, 'user_id' => $userId, 'title' => 'SYSTEM_GREETING', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $newId;
    }

    private function saveAndBroadcast(int $userId, string $conversationId, string $aiResponse): void
    {
        $newMessageId = (string) Str::uuid();
        $now = now();

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId, 'conversation_id' => $conversationId, 'user_id' => $userId,
            'agent' => SentinelAgent::class, 'role' => 'assistant', 'content' => $aiResponse,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        event(new MessageReceived($userId, [
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'agent' => SentinelAgent::class,
            'agent_name' => $this->agentName,
            'sender' => $this->agentName,
            'text' => $aiResponse,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => false,
            'role' => 'assistant',
        ]));
    }
}
