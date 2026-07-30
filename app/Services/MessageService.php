<?php

namespace App\Services;

use App\Actions\SendMessageAction;
use App\Jobs\HandleAgentResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MessageService
{
    public function __construct(
        protected SendMessageAction $sendMessageAction
    ) {}

    public function getIndexMessages(): Collection
    {
        return DB::table('agent_conversation_messages as m')
            ->join('agent_conversations as c', 'm.conversation_id', '=', 'c.id')
            ->join('users as u', 'c.user_id', '=', 'u.id')
            ->where('m.user_id', auth()->id())
            ->orderBy('m.created_at', 'desc')
            ->select(['m.*', 'u.name as bot_real_name', 'm.content as text'])
            ->get();
    }

    public function storeMessage(string $messageId, string $text): ?array
    {
        $context = $this->findConversationContext($messageId);

        if (! $context) {
            return null;
        }

        [$original, $conversation] = $context;

        $newMessageId = $this->sendMessageAction->execute(
            auth()->id(),
            $conversation->user_id,
            $text,
            $original->agent,
            'user'
        );

        HandleAgentResponse::dispatch(auth()->id(), $conversation->id);

        return [
            'id' => $newMessageId,
            'conversation_id' => $conversation->id,
            'agent' => $original->agent,
            'text' => $text,
            'created_at' => now(),
            'role' => 'user',
        ];
    }

    public function destroyConversation(int|string $conversationId): bool
    {
        $conversationExists = DB::table('agent_conversations')->where('id', $conversationId)->exists();

        if (! $conversationExists) {
            return false;
        }

        DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->delete();
        DB::table('agent_conversations')->where('id', $conversationId)->delete();

        return true;
    }

    public function findConversationContext(string $messageId): ?array
    {
        $original = DB::table('agent_conversation_messages')
            ->where('id', $messageId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $original) {
            return null;
        }

        $conversation = DB::table('agent_conversations')->where('id', $original->conversation_id)->first();

        if (! $conversation) {
            return null;
        }

        return [$original, $conversation];
    }
}