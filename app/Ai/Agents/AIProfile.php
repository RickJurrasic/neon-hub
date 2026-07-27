<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;

interface AIProfile extends Agent, Conversational, HasTools
{
    public function triggerFriendRequest(): void;

    public function sendMessage(): void;

    public function createPost(): void;

    public function reactToEvent(): void;
}
