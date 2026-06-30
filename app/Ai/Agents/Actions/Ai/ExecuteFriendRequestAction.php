<?php

namespace App\Actions\Ai;

use App\Actions\SendFriendRequestAction;
use App\Models\User;

class ExecuteFriendRequestAction implements AiAction
{
    public function execute(User $user, array $payload): void
    {
        $recruiterId = 1;

        if ($user->id !== $recruiterId) {
            app(SendFriendRequestAction::class)->execute((int) $user->id, $recruiterId);
        }
    }
}
