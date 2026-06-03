<?php

namespace App\Jobs;

use App\Ai\Agents\SentinelAgent;
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
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // 1. Inicializujeme Agenta a necháme ho, ať si sám vytáhne z DB svou paměť
        $agent = app(SentinelAgent::class)->loadConversation($this->conversationId);
        // 2. Spustíme generování odpovědi
        $aiResponse = $agent->prompt('Respond to the users message. You are a sentinel agent. Be friendly, 20 word response.',
            provider: [
                'gemini',
                'gemini_fallback',
                'groq',
            ])->text;

        $newMessageId = (string) Str::uuid();
        $now = now();

        // 3. Uložíme odpověď bota do databáze
        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $this->conversationId,
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

        $cleanAgentName = 'Sentinel';

        // 4. Vyšleme WebSocket Event pro real-time update na frontendu
        event(new MessageReceived($user->id, [
            'id' => $newMessageId,
            'conversation_id' => $this->conversationId,
            'agent' => SentinelAgent::class,
            'agent_name' => $cleanAgentName,
            'sender' => $cleanAgentName,
            'text' => $aiResponse,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(), // FIX: Posíláme plný timestamp přes WebSockets
            'read' => false,
            'role' => 'assistant',
        ]));
    }
}
