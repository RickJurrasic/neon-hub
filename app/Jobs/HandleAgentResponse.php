<?php

namespace App\Jobs;

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Events\NewActivityAlert;
use App\Events\PostCreated;
use App\Models\Post;
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
        public ?string $agentName = null
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        $conversationId = $this->conversationId;
        $agentUser = null;

        // 🤖 1. URČENÍ BOT PROFILE IDENTITY
        if (! $conversationId) {
            if ($this->agentName) {
                $agentUser = User::where('name', $this->agentName)->first();
            } else {
                $agentUser = User::where('id', '>', 1)->inRandomOrder()->first();
            }

            if (! $agentUser) {
                return;
            }

            $conversationId = DB::table('agent_conversations')
                ->where('user_id', $agentUser->id)
                ->value('id');

            if (! $conversationId) {
                $conversationId = (string) Str::uuid();
                DB::table('agent_conversations')->insert([
                    'id' => $conversationId,
                    'user_id' => $agentUser->id,
                    'title' => 'SYSTEM_GREETING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } else {
            $conversation = DB::table('agent_conversations')->where('id', $conversationId)->first();
            if (! $conversation) {
                return;
            }
            $agentUser = User::find($conversation->user_id);
            if (! $agentUser) {
                return;
            }
        }

        // 🧠 2. INICIALIZACE AGENTA S DYNAMICKOU PERSONOU
        $agentInstance = app(AIAgent::class)->setPersona($agentUser->name);

        // 🔍 3. STAVOVÁ KONTROLA A PŘÍPRAVA PROMPTU
        $lastMessage = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastMessage && $lastMessage->role === 'assistant') {
            return; // Záchranná brzda proti zacyklení
        }

        // 📡 4. GENEROVÁNÍ PŘES LLM (jen groq)
        if (! $lastMessage) {
            $prompt = "The user {$user->name} just joined the NeonHub. Write a very short (max 15 words) greeting matching your specified identity.";
        } else {
            $agentInstance->loadConversation($conversationId);
            $prompt = "Respond to the user's message. Stay strictly in character. Max 20 words.";
        }

        $aiResponse = $agentInstance->prompt($prompt, provider: ['groq'])->text;

        $newMessageId = (string) Str::uuid();
        $now = now();

        // 💾 5. ULOŽENÍ DO DB
        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => AIAgent::class,
            'role' => 'assistant',
            'content' => $aiResponse,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // ⚡ 6. WEBSOCKET DO CHATU
        event(new MessageReceived($user->id, [
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'agent' => AIAgent::class,
            'agent_name' => $agentUser->name,
            'sender' => $agentUser->name,
            'text' => $aiResponse,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => false,
            'role' => 'assistant',
        ]));

        // 🌐 7. GENEROVÁNÍ FEED POSTU POMOCÍ LLM (jen groq)
        $postPrompt = $lastMessage
            ? "Write a short, immersive cyberpunk comment about the user's message. Max 100 characters. Stay in character."
            : 'Write a short, immersive cyberpunk greeting for a new user joining NeonHub. Max 100 characters. Stay in character.';

        $postContent = $agentInstance->prompt($postPrompt, provider: ['groq'])->text;

        // 📷 8. SEED OBRAKU (online image seeder)
        $imageUrl = SeedPostImage::generate();

        $postType = 'AI_FEED';

        $post = Post::create([
            'user_id' => $agentUser->id,
            'content' => $postContent,
            'type' => $postType,
            'latency' => rand(1, 4).'.'.rand(0, 9).'ms',
            'likes_count' => 0,
            'image_url' => $imageUrl,
        ]);

        $formattedPost = [
            'id' => $post->id,
            'author' => $agentUser->name,
            'content' => $post->content,
            'type' => $post->type,
            'time' => $post->latency,
            'likes_count' => $post->likes_count,
            'comments_count' => 0,
            'image' => $post->image_url,
            'image_meta' => null,
            'comments' => [],
        ];

        event(new PostCreated($formattedPost, $user->id));

        // 📢 POST notifikace ve formátu: AI_ACTION: POST / {userName} has created a post
        $postAlertMessage = "{$agentUser->name} has created a post";
        event(new NewActivityAlert($user->id, $postAlertMessage));
    }
}
