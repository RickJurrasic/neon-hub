<?php

namespace App\Ai\Agents;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class SentinelAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    protected array $history = [];

    public function loadConversation(string $conversationId): self
    {
        $this->history = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($msg) => new Message($msg->role, $msg->content))
            ->all();

        return $this;
    }

    public function withHistory(array $history): self
    {
        $this->history = $history;

        return $this;
    }

    public function instructions(): Stringable|string
    {
        return 'You are a Sentinel, the strict guardian of the NeonHub server. You speak in a terse, technical manner. Respond directly.';
    }

    public function messages(): iterable
    {
        return $this->history;
    }

    public function tools(): iterable
    {
        return [];
    }
}
