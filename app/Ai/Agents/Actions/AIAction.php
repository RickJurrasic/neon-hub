<?php

namespace App\Actions\Ai;

use App\Models\User;

interface AiAction
{
    public function execute(User $user, array $payload): void;
}
