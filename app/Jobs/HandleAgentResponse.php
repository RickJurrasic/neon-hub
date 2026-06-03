<?php

namespace App\Jobs;

use App\Ai\Agents\SentinelAgent;
use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleAgentResponse implements ShouldQueue
{
    use Queueable;

    // $conversationId je nepovinné. Pokud chybí, job si ho najde nebo založí (při úvodním pozdravu)
    public function __construct(
        public int $userId,
        public ?string $conversationId = null,
        public string $agentName = 'SENTINEL_01'
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // 1. Zjistíme ID konverzace
        $conversationId = $this->conversationId;
        if (! $conversationId) {
            $conversationId = DB::table('agent_conversations')
                ->where('user_id', $user->id)
                ->value('id');
        }

        // Pokud konverzace v DB vůbec neexistuje, bezpečně ji založíme
        if (! $conversationId) {
            $conversationId = (string) Str::uuid();
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'user_id' => $user->id,
                'title' => 'SYSTEM_GREETING',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. STAVOVÁ KONTROLA: Podíváme se na úplně poslední zprávu v této konverzaci
        $lastMessage = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->first();

        $prompt = '';
        $agent = null;

        if (! $lastMessage) {
            // STAV A: Konverzace je úplně prázdná -> Generujeme úvodní pozdrav
            $prompt = "The user {$user->name} just joined the NeonHub. Write a very short (max 15 words), terse, technical greeting.";
            $agent = SentinelAgent::make();
        } elseif ($lastMessage->role === 'assistant') {
            // STAV B: ZÁCHRANNÁ BRZDA PRO REFRESH STRÁNKY!
            // Poslední zprávu v DB poslal bot. Nebudeme psát nic dalšího a job bezpečně ukončíme.
            return;
        } else {
            // STAV C: Poslední zprávu poslal uživatel -> Klasická odpověď s načtením historie konverzace
            $prompt = 'Respond to the users message. You are a sentinel agent. Be friendly, 20 word response.';
            $agent = app(SentinelAgent::class)->loadConversation($conversationId);
        }

        // 3. Spustíme generování odpovědi přes LLM
        $aiResponse = $agent->prompt($prompt, provider: [
            'gemini',
            'gemini_fallback',
            'groq',
        ])->text;

        $newMessageId = (string) Str::uuid();
        $now = now();

        // 4. Uložíme zprávu bota do databáze
        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => SentinelAgent::class,
            'role' => 'assistant',
            'content' => $aiResponse,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $cleanAgentName = 'Sentinel';

        // 5. Odešleme kompletní a čistá data přes WebSocket do Vue
        event(new MessageReceived($user->id, [
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'agent' => SentinelAgent::class,
            'agent_name' => $cleanAgentName,
            'sender' => $cleanAgentName,
            'text' => $aiResponse,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => false,
            'role' => 'assistant',
        ]));
    }
}
