<?php

namespace App\Ai\Agents\Actions;

use App\Models\User;

interface AIAction
{
    /**
     * Spustí konkrétní AI akci pro daného uživatele/bota.
     *
     * @param array<string, mixed> $payload
     */
    public function execute(User $user, array $payload): void;
}