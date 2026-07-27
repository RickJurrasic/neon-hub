<?php

namespace App\Actions;

use App\Events\MessageReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendMessageAction
{
    public function execute(int $senderId, int $recipientId, string $content, ?string $agentName = null, string $role = 'assistant'): string
    {
        $now = now();
        $recruiterId = 1;

        $botId = $role === 'assistant' ? $senderId : $recipientId;
        $conversationId = $this->ensureConversationExists($botId);
        $agentClass = $this->resolveAgentClass($agentName, $conversationId);

        $messageId = (string) Str::uuid();
        DB::table('agent_conversation_messages')->insert([
            'id' => $messageId, 'conversation_id' => $conversationId, 'user_id' => $recruiterId,
            'agent' => $agentClass, 'role' => $role, 'content' => $content,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $cleanAgentName = $agentClass ? str_replace('App\\Ai\\Agents\\', '', $agentClass) : null;

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

    private function ensureConversationExists(int $botId): string
    {
        $id = DB::table('agent_conversations')->where('user_id', $botId)->value('id');

        return $id ?? $this->createNewConversation($botId);
    }

    private function createNewConversation(int $botId): string
    {
        $newId = (string) Str::uuid();
        DB::table('agent_conversations')->insert([
            'id' => $newId, 'user_id' => $botId, 'title' => 'SECURE_CHANNEL', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $newId;
    }

    private function resolveAgentClass(?string $agentName, string $conversationId): ?string
    {
        if (! $agentName) {
            return DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->whereNotNull('agent')->value('agent');
        }

        return str_starts_with($agentName, 'App\\') ? $agentName : 'App\\Ai\\Agents\\'.str_replace('_', '', $agentName).'Agent';
    }
}
