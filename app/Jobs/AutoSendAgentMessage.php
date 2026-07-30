<?php

namespace App\Jobs;

use App\Ai\Agents\SentinelAgent;
use App\Events\MessageReceived;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class AutoSendAgentMessage implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Počet pokusů o opakováni jobu při selhání.
     */
    public int $tries = 3;

    /**
     * Odmlka mezi opakovanými pokusy (v sekundách).
     */
    public int $backoff = 5;

    /**
     * Maximální doba běhu jobu (v sekundách).
     */
    public int $timeout = 30;

    public function __construct(
        public readonly int $userId,
        public readonly string $agentName = 'SENTINEL_01'
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        $bot = User::where('name', $this->agentName)->first();

        if (! $user || ! $bot) {
            Log::warning("AutoSendAgentMessage skipped: User {$this->userId} or Bot {$this->agentName} not found.");

            return;
        }

        $conversationId = $this->ensureConversationId($user->id);
        $aiResponse = $this->generateGreeting($user->name);

        if (empty($aiResponse)) {
            Log::warning("AutoSendAgentMessage aborted: Empty AI greeting generated for User {$user->name}.");

            return;
        }

        $this->saveAndBroadcast($user->id, $conversationId, $aiResponse);
    }

    private function generateGreeting(string $userName): string
    {
        $response = SentinelAgent::make()->prompt(
            "The user {$userName} just joined NeonHub. Write a very short (max 15 words), terse, technical greeting.",
            provider: ['gemini', 'gemini_fallback', 'groq']
        );

        return Str::of($response->text ?? '')
            ->trim()
            ->replace(['"', "'"], '')
            ->toString();
    }

    private function ensureConversationId(int $userId): string
    {
        $existingId = DB::table('agent_conversations')
            ->where('user_id', $userId)
            ->value('id');

        if ($existingId) {
            return (string) $existingId;
        }

        $newId = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            'id' => $newId,
            'user_id' => $userId,
            'title' => 'SYSTEM_GREETING',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $newId;
    }

    private function saveAndBroadcast(int $userId, string $conversationId, string $aiResponse): void
    {
        $newMessageId = (string) Str::uuid();
        $now = now();

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $conversationId,
            'user_id' => $userId,
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

        Log::info("AutoSendAgentMessage broadcasted for User {$userId} from {$this->agentName}");
    }

    /**
     * Ošetření trvalého selhání jobu.
     */
    public function failed(Throwable $exception): void
    {
        Log::error("AutoSendAgentMessage failed permanently for User {$this->userId}: {$exception->getMessage()}");
    }
}