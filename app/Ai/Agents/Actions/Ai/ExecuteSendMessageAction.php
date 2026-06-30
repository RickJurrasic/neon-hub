<?php

namespace App\Actions\Ai;

use App\Actions\SendMessageAction;
use App\Ai\Agents\AIAgent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExecuteSendMessageAction implements AiAction
{
    public function execute(User $user, array $payload): void
    {
        $recruiterId = 1;
        $agent = (new AIAgent())->withPersona($user->name);

        $conversation = DB::table('agent_conversations')
            ->where('user_id', $user->id)
            ->first();

        $conversationId = $conversation ? $conversation->id : null;
        $lastMessage = null;

        if ($conversationId) {
            $lastMessage = DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->first();

            $agent->loadConversation((string) $conversationId);
        }

        if ($lastMessage && $lastMessage->role !== 'user') {
            Log::info("AI action skipped: Bot {$user->name} waiting for user response.");

            return;
        }

        $botMessageText = $agent->prompt(
            'Generate the next short message reply to the user. Keep it strictly under 2 short sentences. Do not wrap in quotes. Speak completely in English matching your exact persona.',
            provider: ['groq']
        )->text;

        app(SendMessageAction::class)->execute((int) $user->id, $recruiterId, $botMessageText, $user->name);

        Log::info("AI action broadcasted: send_message from bot [{$user->name}] to user 1");
    }
}
