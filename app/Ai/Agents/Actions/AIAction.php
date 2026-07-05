<?php

namespace App\Ai\Agents\Actions;

use App\Models\User;

interface AiAction
{
    public function execute(User $user, array $payload): void;
}
