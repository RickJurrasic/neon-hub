<?php

namespace App\Jobs;

use App\Ai\Agents\SentinelAgent;
use App\Events\MessageReceived;
use App\Events\PostCreated; // <--- Import nového eventu
use App\Models\Post;         // <--- Import tvého modelu Post
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HandleAgentResponse implements ShouldQueue
{
    use Queueable;

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

        // 2. STAVOVÁ KONTROLA
        $lastMessage = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->first();

        $prompt = '';
        $agent = null;

        if (! $lastMessage) {
            $prompt = "The user {$user->name} just joined the NeonHub. Write a very short (max 15 words), terse, technical greeting.";
            $agent = SentinelAgent::make();
        } elseif ($lastMessage->role === 'assistant') {
            return; // Záchranná brzda
        } else {
            $prompt = 'Respond to the users message. You are a sentinel agent. Be friendly, 20 word response.';
            $agent = app(SentinelAgent::class)->loadConversation($conversationId);
        }

        // 3. Generování přes LLM
        $aiResponse = $agent->prompt($prompt, provider: [
            'gemini',
            'gemini_fallback',
            'groq',
        ])->text;

        $newMessageId = (string) Str::uuid();
        $now = now();

        // 4. Uložení zprávy chatu do DB
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

        // 5. Odeslání zprávy do chatu přes WebSocket
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

        // Najdeme DB instanci bota podle jména (např. SENTINEL_01), ať máme správné user_id
        $agentUser = User::where('name', $this->agentName)->first();

        // Dynamický kyberpunkový text pro feed podle toho, jestli bot jen zdraví, nebo reaguje
        $postContent = ! $lastMessage
            ? "NETWORK_ALERT: New node connection established with subject [{$user->name}]. Telemetry sync initialized."
            : "DATA_STREAM: Processing incoming packets from node [{$user->name}]. System responses shifting.";

        $postType = ! $lastMessage ? 'SYSTEM_LOG' : 'DATA_STREAM';

        // Zápis do tabulky posts přes tvůj Eloquent model
        $post = Post::create([
            'user_id' => $agentUser?->id ?? $user->id, // Fallback na usera, kdyby bot v DB chyběl
            'content' => $postContent,
            'type' => $postType,
            'latency' => rand(1, 4).'.'.rand(0, 9).'ms',
            'likes_count' => rand(10, 95),
        ]);

        // Formátování payloadu do podoby, kterou striktně chroustá tvé Vue a Pinia
        $formattedPost = [
            'id' => $post->id,
            'author' => $agentUser?->name ?? $this->agentName,
            'content' => $post->content,
            'type' => $post->type,
            'time' => $post->latency,
            'likes_count' => $post->likes_count,
            'comments_count' => 0,
            'image' => null,
            'image_meta' => null,
            'comments' => [],
        ];

        // Odpálení broadcastu na frontend uživatele
        event(new PostCreated($formattedPost, $user->id));
    }
}
