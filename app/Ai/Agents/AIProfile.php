<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

/**
 * Kontrakt pro samostatné AI profily působící v aplikaci NeonHub.
 */
interface AIProfile extends Agent, Conversational, HasTools
{
    /**
     * Vyvolá odeslání žádosti o přátelství uživateli.
     */
    public function triggerFriendRequest(): void;

    /**
     * Vygeneruje a odešle zprávu do konverzace.
     */
    public function sendMessage(): void;

    /**
     * Vytvoří nový příspěvek na profilu AI bota.
     */
    public function createPost(): void;

    /**
     * Zreaguje na systémovou událost nebo akci jiného uživatele.
     */
    public function reactToEvent(): void;
}