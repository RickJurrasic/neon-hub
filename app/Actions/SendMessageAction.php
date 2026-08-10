<?php

namespace App\Actions;

use App\Events\MessageReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendMessageAction
{
    /**
     * Odeslat zprávu v agentní konverzaci a vyvolat událost pro WebSocket.
     */
    public function execute(
        int $senderId,
        int $recipientId,
        string $content,
        ?string $agentName = null,
        string $role = 'assistant'
    ): string {
        $now = now();
        $recruiterId = 1;

        $botId = $role === 'assistant' ? $senderId : $recipientId;
        $conversationId = $this->ensureConversationExists($botId);
        $agentClass = $this->resolveAgentClass($agentName, $conversationId);
        $cleanAgentName = $this->formatAgentName($agentClass);

        $messageId = (string) Str::uuid();

        DB::table('agent_conversation_messages')->insert([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'user_id' => $recruiterId,
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

        event(new MessageReceived($recruiterId, [
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
     * Zjistí ID existující konverzace nebo vytvoří novou.
     */
    private function ensureConversationExists(int $botId): string
    {
        $conversationId = DB::table('agent_conversations')
            ->where('user_id', $botId)
            ->value('id');

        return $conversationId ?? $this->createNewConversation($botId);
    }

    /**
     * Vytvoří novou konverzaci pro daného bota.
     */
    private function createNewConversation(int $botId): string
    {
        $newId = (string) Str::uuid();

        DB::table('agent_conversations')->insert([
            'id' => $newId,
            'user_id' => $botId,
            'title' => 'SECURE_CHANNEL',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $newId;
    }

    /**
     * Vyřeší plný název třídy AI agenta.
     */
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

    /**
     * Převede FQCN třídu na krátký název (např. App\Ai\Agents\SentinelAgent -> SentinelAgent).
     */
    private function formatAgentName(?string $agentClass): ?string
    {
        if (! $agentClass) {
            return null;
        }

        return class_basename($agentClass);
    }
}
