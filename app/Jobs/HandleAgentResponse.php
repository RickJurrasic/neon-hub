<?php

namespace App\Jobs;

use App\Ai\Agents\AIAgent;
use App\Events\MessageReceived;
use App\Events\NewActivityAlert;
use App\Events\PostCreated;
use App\Models\Post;
use App\Models\SeedPostImage;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HandleAgentResponse implements ShouldQueue
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
        public readonly ?string $conversationId = null,
        public readonly ?string $agentName = null
    ) {}

    public function handle(AIAgent $agent): void
    {
        $user = User::find($this->userId);
        $agentUser = $this->resolveAgentUser();

        if (! $user || ! $agentUser) {
            Log::warning("HandleAgentResponse skipped: User {$this->userId} or Agent not found.");

            return;
        }

        $activeConversationId = $this->conversationId ?? $this->ensureConversationId($user, $agentUser);

        if ($this->isLastMessageFromAssistant($activeConversationId)) {
            Log::info("HandleAgentResponse skipped: Last message in conversation {$activeConversationId} was already from assistant.");

            return;
        }

        // Využijeme vylepšené withPersona(), které akceptuje rovnou instanci User
        $agentInstance = $agent->withPersona($agentUser);

        $lastMessage = DB::table('agent_conversation_messages')
            ->where('conversation_id', $activeConversationId)
            ->orderBy('created_at', 'desc')
            ->first();

        $aiChatResponse = $this->generateChatResponse($agentInstance, $user, $lastMessage, $activeConversationId);

        // The agent was resolved above, but the conversation may have been
        // purged (MessageService::destroyConversation) while this job was
        // queued or during the LLM call. Re-check immediately before writing
        // so we never orphan an assistant message into a deleted conversation
        // or fire a stale MessageReceived to a purged channel.
        if (! $this->conversationExists($activeConversationId)) {
            Log::info("HandleAgentResponse skipped: Conversation {$activeConversationId} no longer exists (purged).");

            return;
        }

        if (filled($aiChatResponse)) {
            $this->saveAndBroadcastMessage($agentUser, $user, $activeConversationId, $aiChatResponse);
        }

        $this->createFeedPost($agentInstance, $agentUser, $user, $lastMessage);
    }

    private function isLastMessageFromAssistant(string $conversationId): bool
    {
        return DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'desc')
            ->value('role') === 'assistant';
    }

    /**
     * Whether the conversation still exists (i.e. has not been purged by
     * MessageService::destroyConversation while this job was queued or
     * executing). Guards the write path so a queued job cannot re-insert
     * an orphaned assistant message or broadcast to a purged channel.
     */
    private function conversationExists(string $conversationId): bool
    {
        return DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->exists();
    }

    private function resolveAgentUser(): ?User
    {
        if ($this->conversationId) {
            return $this->getAgentFromConversation($this->conversationId);
        }

        if ($this->agentName) {
            return User::where('name', $this->agentName)->first();
        }

        return User::where('is_ai', true)->inRandomOrder()->first();
    }

    private function getAgentFromConversation(string $conversationId): ?User
    {
        $conversation = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->first();

        if (! $conversation) {
            return null;
        }

        /** @var User|null */
        return User::find($conversation->agent_user_id ?? $conversation->user_id);
    }

    private function ensureConversationId(User $user, User $agentUser): string
    {
        $existingId = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->where('agent_user_id', $agentUser->id)
            ->value('id');

        if ($existingId) {
            return (string) $existingId;
        }

        $newId = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            'id' => $newId,
            'user_id' => $user->id,
            'agent_user_id' => $agentUser->id,
            'title' => 'SYSTEM_GREETING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $newId;
    }

    private function generateChatResponse(
        AIAgent $agentInstance,
        User $user,
        ?object $lastMessage,
        string $conversationId
    ): string {
        if (! $lastMessage) {
            $prompt = "The user {$user->name} just joined NeonHub. Write a very short (max 15 words) greeting matching your specified identity.";
        } else {
            $agentInstance->loadConversation($conversationId);
            $prompt = "Respond to the user's message. Stay strictly in character. Max 20 words.";
        }

        $response = $agentInstance->prompt($prompt, provider: ['groq']);

        return Str::of($response->text ?? '')
            ->trim()
            ->replace(['"', "'"], '')
            ->toString();
    }

    private function saveAndBroadcastMessage(
        User $agentUser,
        User $user,
        string $conversationId,
        string $aiResponse
    ): void {
        $newMessageId = (string) Str::uuid();
        $now = now();

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => AIAgent::class,
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

        Log::info("HandleAgentResponse: Message broadcasted from agent [{$agentUser->name}] to user [{$user->id}]");
    }

    private function createFeedPost(
        AIAgent $agentInstance,
        User $agentUser,
        User $user,
        ?object $lastMessage
    ): void {
        $postPrompt = $lastMessage
            ? "Write a short, immersive cyberpunk comment about the user's message. Max 100 characters. Stay in character."
            : 'Write a short, immersive cyberpunk greeting for a new user joining NeonHub. Max 100 characters. Stay in character.';

        $response = $agentInstance->prompt($postPrompt, provider: ['groq']);

        $postContent = Str::of($response->text ?? '')
            ->trim()
            ->replace(['"', "'"], '')
            ->toString();

        if (empty($postContent)) {
            Log::warning("HandleAgentResponse: Empty post content generated by agent [{$agentUser->name}]");

            return;
        }

        $imageUrl = class_exists('\App\Models\SeedPostImage')
            ? SeedPostImage::generate()
            : null;

        $post = Post::create([
            'user_id' => $agentUser->id,
            'content' => $postContent,
            'type' => 'AI_FEED',
            'latency' => random_int(1, 4).'.'.random_int(0, 9).'ms',
            'likes_count' => 0,
            'image_url' => $imageUrl,
        ]);

        event(new PostCreated([
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
        ], $user->id));

        event(new NewActivityAlert($user->id, "{$agentUser->name} has created a post"));

        Log::info("HandleAgentResponse: Feed post [{$post->id}] created by agent [{$agentUser->name}]");
    }

    /**
     * Ošetření trvalého selhání jobu.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("HandleAgentResponse failed permanently for User {$this->userId}: {$exception->getMessage()}");
    }
}
