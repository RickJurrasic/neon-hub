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
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $bot = User::where('name', $this->agentName)->first();

        if (! $user || ! $bot) {
            return;
        }

        $agent = SentinelAgent::make();
        $now = now();

        $aiResponse = $agent->prompt(
            "The user {$user->name} just joined the NeonHub. Write a very short (max 15 words), terse, technical greeting.",
            provider: [
                'gemini',
                'gemini_fallback',
                'groq',
            ]
        )->text;

        $newMessageId = (string) Str::uuid();
        $conversationId = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->value('id') ?? (string) Str::uuid();

        if (! DB::table('agent_conversations')->where('id', $conversationId)->exists()) {
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'user_id' => $user->id,
                'title' => 'SYSTEM_GREETING',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => SentinelAgent::class,
            'role' => 'assistant',
            'content' => $aiResponse,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Odpálíme MessageReceived event s COMPLETNÍMI daty pro realtime broadcast
        event(new MessageReceived($user->id, [
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
