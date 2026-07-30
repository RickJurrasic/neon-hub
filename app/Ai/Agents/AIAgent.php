<?php

namespace App\Ai\Agents;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class AIAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @var array<Message>
     */
    protected array $history = [];

    protected ?string $personaInstructions = null;

    /**
     * Načte historii konverzace z databáze a převede ji na objekty Message.
     */
    public function loadConversation(string $conversationId): self
    {
        $this->history = DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('created_at', 'asc')
            ->get(['role', 'content'])
            ->map(fn ($msg) => new Message($msg->role, $msg->content))
            ->all();

        return $this;
    }

    /**
     * Nastaví historii zpráv ručně.
     *
     * @param array<Message> $history
     */
    public function withHistory(array $history): self
    {
        $this->history = $history;

        return $this;
    }

    /**
     * 🎭 Dynamické nastavení identity přímo z databáze podle uživatele nebo jména bota.
     */
    public function withPersona(User|string $botOrName): self
    {
        $bot = $botOrName instanceof User
            ? $botOrName
            : User::where('name', $botOrName)->first(['name', 'bio']);

        $botName = $bot?->name ?? (is_string($botOrName) ? $botOrName : 'UNKNOWN_ENTITY');

        if ($bot && filled($bot->bio)) {
            $this->personaInstructions = "You are {$bot->name}. {$bot->bio} Maintain a sharp, professional, yet friendly and helpful cyberpunk tone. Respond in English.";
        } else {
            $this->personaInstructions = "You are {$botName}, an autonomous AI entity operating within NeonHub. Maintain a sharp, high-tech cyberpunk tone.";
        }

        return $this;
    }

    public function instructions(): Stringable|string
    {
        return $this->personaInstructions
            ?? 'You are an autonomous AI entity operating within NeonHub. Maintain a sharp, high-tech cyberpunk tone.';
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