<?php

namespace App\Ai\Agents\Actions;

use App\Models\User;

interface AIAction
{
    public function execute(User $user, array $payload): void;
}
