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
    ) {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $agentUser = $this->resolveAgentUser();

        if (! $user || ! $agentUser) {
            return;
        }

        $this->conversationId = $this->ensureConversationId($agentUser);

        if ($this->isLastMessageFromAssistant()) {
            return;
        }

        $agentInstance = app(AIAgent::class)->withPersona($agentUser->name);

        $lastMessage = DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->orderBy('created_at', 'desc')
            ->first();

        $this->saveAndBroadcastMessage($agentUser, $user, $this->generateChatResponse($agentInstance, $user, $lastMessage));
        $this->createFeedPost($agentInstance, $agentUser, $user, $lastMessage);
    }

    private function isLastMessageFromAssistant(): bool
    {
        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $this->conversationId)
            ->orderBy('created_at', 'desc')
            ->value('role') === 'assistant';
    }

    private function resolveAgentUser(): ?User
    {
        if ($this->conversationId) {
            return $this->getAgentFromConversation();
        }

        return $this->agentName
            ? User::where('name', $this->agentName)->first()
            : User::where('id', '>', 1)->inRandomOrder()->first();
    }

    private function getAgentFromConversation(): ?User
    {
        $con = DB::table('agent_conversations')->where('id', $this->conversationId)->first();

        return $con ? User::find($con->user_id) : null;
    }

    private function ensureConversationId(User $agentUser): string
    {
        $id = DB::table('agent_conversations')->where('user_id', $agentUser->id)->value('id');

        return $id ?? $this->createNewConversationId($agentUser);
    }

    private function createNewConversationId(User $agentUser): string
    {
        $newId = (string) Str::uuid();
        DB::table('agent_conversations')->insert([
            'id' => $newId, 'user_id' => $agentUser->id, 'title' => 'SYSTEM_GREETING', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $newId;
    }

    private function generateChatResponse(AIAgent $agentInstance, User $user, ?object $lastMessage): string
    {
        if (! $lastMessage) {
            return $agentInstance->prompt("The user {$user->name} just joined the NeonHub. Write a very short (max 15 words) greeting matching your specified identity.", provider: ['groq'])->text;
        }

        $agentInstance->loadConversation($this->conversationId);

        return $agentInstance->prompt("Respond to the user's message. Stay strictly in character. Max 20 words.", provider: ['groq'])->text;
    }

    private function saveAndBroadcastMessage(User $agentUser, User $user, string $aiResponse): void
    {
        $newMessageId = (string) Str::uuid();
        $now = now();

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId, 'conversation_id' => $this->conversationId, 'user_id' => $user->id,
            'agent' => AIAgent::class, 'role' => 'assistant', 'content' => $aiResponse,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        event(new MessageReceived($user->id, [
            'id' => $newMessageId, 'conversation_id' => $this->conversationId, 'agent' => AIAgent::class,
            'agent_name' => $agentUser->name, 'sender' => $agentUser->name, 'text' => $aiResponse,
            'time' => $now->toTimeString(), 'created_at' => $now->toIso8601String(), 'read' => false, 'role' => 'assistant',
        ]));
    }

    private function createFeedPost(AIAgent $agentInstance, User $agentUser, User $user, ?object $lastMessage): void
    {
        $postPrompt = $lastMessage
            ? "Write a short, immersive cyberpunk comment about the user's message. Max 100 characters. Stay in character."
            : 'Write a short, immersive cyberpunk greeting for a new user joining NeonHub. Max 100 characters. Stay in character.';

        $post = Post::create([
            'user_id' => $agentUser->id, 'content' => $agentInstance->prompt($postPrompt, provider: ['groq'])->text, 'type' => 'AI_FEED',
            'latency' => rand(1, 4).'.'.rand(0, 9).'ms', 'likes_count' => 0, 'image_url' => SeedPostImage::generate(),
        ]);

        event(new PostCreated([
            'id' => $post->id, 'author' => $agentUser->name, 'content' => $post->content, 'type' => $post->type,
            'time' => $post->latency, 'likes_count' => $post->likes_count, 'comments_count' => 0, 'image' => $post->image_url,
            'image_meta' => null, 'comments' => [],
        ], $user->id));

        event(new NewActivityAlert($user->id, "{$agentUser->name} has created a post"));
    }
}
