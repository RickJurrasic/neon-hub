<?php

namespace App\Ai\Agents\Actions\Ai;

use App\Actions\SendFriendRequestAction;
use App\Ai\Agents\Actions\AIAction;
use App\Models\User;

class ExecuteFriendRequestAction implements AIAction
{
    public function __construct(
        private readonly SendFriendRequestAction $sendFriendRequestAction
    ) {}

    public function execute(User $user, array $payload): void
    {
        $recipientId = (int) ($payload['recipient_id'] ?? 1);

        // Zamezíme tomu, aby bot posílal žádost sám sobě
        if ($user->id !== $recipientId) {
            $this->sendFriendRequestAction->execute($user->id, $recipientId);
        }
    }
}
