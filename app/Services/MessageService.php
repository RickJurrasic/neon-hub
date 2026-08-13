<?php

namespace App\Services;

use App\Actions\SendMessageAction;
use App\Jobs\HandleAgentResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class MessageService
{
    public function __construct(
        protected SendMessageAction $sendMessageAction
    ) {}

    /**
     * Načtení zpráv přes Query Builder.
     *
     * @return Collection<int, stdClass>
     */
    public function getIndexMessages(int $userId): Collection
    {
        /** @var Collection<int, stdClass> */
        return DB::table('agent_conversations')
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function storeMessage(string $messageId, string $text): ?array
    {
        $context = $this->findConversationContext($messageId);

        if (! $context) {
            return null;
        }

        [$original, $conversation] = $context;

        $newMessageId = $this->sendMessageAction->execute(
            (int) auth()->id(),
            (int) $conversation->user_id,
            $text,
            (string) $original->agent,
            'user'
        );

        HandleAgentResponse::dispatch((int) auth()->id(), (string) $conversation->id);

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
     */
    public function destroyConversation(string $conversationId): void
    {
        $userId = (int) auth()->id();

        $owned = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->exists();

        if (! $owned) {
            throw new AuthorizationException();
        }

        DB::transaction(function () use ($conversationId, $userId): void {
            DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->delete();

            DB::table('agent_conversations')
                ->where('id', $conversationId)
                ->where('user_id', $userId)
                ->delete();
        });
    }

    /**
     * @return array{0: stdClass, 1: stdClass}|null
     */
    public function findConversationContext(string $messageId): ?array
    {
        /** @var stdClass|null $original */
        $original = DB::table('agent_conversation_messages')
            ->where('id', $messageId)
            ->where('user_id', (int) auth()->id())
            ->first();

        if (! $original) {
            return null;
        }

        /** @var stdClass|null $conversation */
        $conversation = DB::table('agent_conversations')
            ->where('id', $original->conversation_id)
            ->first();

        if (! $conversation) {
            return null;
        }

        return [$original, $conversation];
    }
}
