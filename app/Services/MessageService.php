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

    /**
     * Načtení zpráv přes čistý Eloquent. Opraveno where_id na user_id.
     */
    public function getIndexMessages(int $userId)
    {
        return AgentConversation::with(['messages', 'sender'])
            ->where('user_id', $userId)
            ->latest()
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

    /**
     * Smaže konverzaci včetně zpráv. 
     * Transakce zajistí, že pokud něco selže, databáze se nepoškodí.
     */
    public function destroyConversation(int $conversationId): void
    {
        DB::transaction(function () use ($conversationId) {
            $conversation = AgentConversation::findOrFail($conversationId);
            $conversation->messages()->delete();
            $conversation->delete();
        });
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