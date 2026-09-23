<?php

namespace App\Actions;

use App\Events\MessageReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendMessageAction
{
    /**
     * Persist a message in an agent conversation and broadcast it.
     *
     * Ownership contract (Laravel AI SDK convention):
     *  - agent_conversations.user_id        = the human owner (inbox / delete gate)
     *  - agent_conversations.agent_user_id  = the AI/bot identity this conv is routed to
     *  - agent_conversation_messages.user_id = the human owner (same for user & assistant roles)
     *
     * Previously this hardcoded recruiterId = 1 and created conversations
     * owned by the bot (user_id = $botId), which made them invisible to the
     * human inbox and un-deletable through the owner-gated
     * conversations.destroy route. Both defects are fixed below.
     */
    public function execute(
        int $senderId,
        int $recipientId,
        string $content,
        ?string $agentName = null,
        string $role = 'assistant'
    ): string {
        $now = now();

        // For an assistant (outgoing bot) message the human is the recipient;
        // for a user (incoming human) message the human is the sender.
        $humanId = $role === 'assistant' ? $recipientId : $senderId;
        $botId = $role === 'assistant' ? $senderId : $recipientId;

        $conversationId = $this->ensureConversationExists(
            $humanId,
            $botId
        );

        $agentClass = $this->resolveAgentClass($agentName, $conversationId);
        $cleanAgentName = $this->formatAgentName($agentClass);

        $messageId = (string) Str::uuid();

        DB::table('agent_conversation_messages')->insert([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'user_id' => $humanId,
            'agent' => $agentClass,
            'role' => $role,
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        event(new MessageReceived($humanId, [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'agent' => $agentClass,
            'agent_name' => $role === 'assistant' ? $cleanAgentName : null,
            'sender' => $role === 'user' ? 'YOU' : ($cleanAgentName ?? 'BOT'),
            'text' => $content,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => false,
            'role' => $role,
        ]));

        return $messageId;
    }

    /**
     * Resolve the human-owned conversation, optionally scoped to a specific bot.
     * Creates a human-owned conversation (bot on agent_user_id) when missing.
     */
    private function ensureConversationExists(int $humanId, ?int $botId = null): string
    {
        $query = DB::table('agent_conversations')
            ->where('user_id', $humanId);

        if ($botId !== null) {
            $query->where('agent_user_id', $botId);
        }

        $conversationId = $query->value('id');

        return $conversationId ?? $this->createNewConversation($humanId, $botId);
    }

    private function createNewConversation(int $humanId, ?int $botId = null): string
    {
        // Never leave agent_user_id NULL. When the caller (a user message)
        // did not supply a bot ID, fall back to a random AI bot so the human's
        // identity is never resolved as the agent — this closes the
        // prompt-injection boundary where getAgentFromConversation would
        // fall back to user_id (the human) and inject their name/bio into
        // the LLM system prompt via withPersona().
        if ($botId === null) {
            $botId = (int) DB::table('users')
                ->where('is_ai', true)
                ->inRandomOrder()
                ->value('id');
        }

        $newId = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            'id' => $newId,
            'user_id' => $humanId,
            'agent_user_id' => $botId,
            'title' => 'SECURE_CHANNEL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $newId;
    }

    private function resolveAgentClass(?string $agentName, string $conversationId): ?string
    {
        if (! $agentName) {
            return DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->whereNotNull('agent')
                ->value('agent');
        }

        if (str_starts_with($agentName, 'App\\')) {
            return $agentName;
        }

        $formattedName = Str::studly(str_replace('_', ' ', $agentName));

        return "App\\Ai\\Agents\\{$formattedName}Agent";
    }

    private function formatAgentName(?string $agentClass): ?string
    {
        if (! $agentClass) {
            return null;
        }

        return class_basename($agentClass);
    }
}
