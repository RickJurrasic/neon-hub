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

    protected array $history = [];

    protected ?string $personaInstructions = null;

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

    /**
     * 🎭 Dynamické nastavení identity přímo z databáze podle jména bota
     */
    public function withPersona(string $botName): self
    {
        // Najdeme uživatele v DB podle jména
        $bot = User::where('name', $botName)->first();

        if ($bot !== null && $bot->bio !== null && $bot->bio !== '') {
            // Použijeme bio z DB a přidáme instrukci pro tón komunikace
            $this->personaInstructions = "You are {$bot->name}. {$bot->bio} Maintain a sharp, professional, yet friendly and helpful cyberpunk tone. Respond in English.";
        } else {
            // Fallback, pokud bot v DB není
            $this->personaInstructions = "You are {$botName}, an autonomous AI entity operating within NeonHub. Maintain a sharp, high-tech cyberpunk tone.";
        }

        return $this;
    }

    public function instructions(): Stringable|string
    {
        return $this->personaInstructions ?? 'You are an autonomous AI entity operating within NeonHub. Maintain a sharp, high-tech cyberpunk tone.';
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
