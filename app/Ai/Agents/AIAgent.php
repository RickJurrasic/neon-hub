<?php

namespace App\Ai\Agents;

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

    protected ?string $personaInstructions = null; // <-- Sem si uložíme dynamický prompt

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
     * 🎭 Dynamické nastavení identity podle jména bota z DB Seederu
     */
    public function setPersona(string $botName): self
    {
        if (str_starts_with($botName, 'SENTINEL')) {
            $this->personaInstructions = 'You are SENTINEL, the strict guardian of the NeonHub server. You speak in a terse, technical manner. Respond directly and coldly.';
        } elseif (str_starts_with($botName, 'CYPHER')) {
            $this->personaInstructions = 'You are CYPHER, an underground rogue hacker. You speak in cryptic, cynical cyber-slang. Use lowercase, tech jargon, and short sentences.';
        } elseif (str_starts_with($botName, 'MATRIX')) {
            $this->personaInstructions = 'You are MATRIX, a corporate AI system executive. You are extremely formal, polite, precise, and obsessed with system optimization and protocols.';
        } else {
            // Generický cyberpunk fallback pro jakékoliv jiné jméno
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
