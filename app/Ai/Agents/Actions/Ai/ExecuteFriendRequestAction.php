<?php

namespace App\Ai\Agents\Actions\Ai;

use App\Actions\SendFriendRequestAction;
use App\Ai\Agents\Actions\AIAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ExecuteFriendRequestAction implements AIAction
{
    public function __construct(
        private readonly SendFriendRequestAction $sendFriendRequestAction
    ) {}

    public function execute(User $user, array $payload): void
    {
        $recipientId = $payload['recipient_id'] ?? $payload['demo_owner_id'] ?? null;

        if (! $recipientId) {
            Log::warning('friend_request: missing recipient_id in payload.');

            return;
        }

        $recipient = User::find($recipientId);

        if (! $recipient instanceof User || $recipient->is_ai) {
            Log::warning("friend_request: recipient_id [{$recipientId}] is not a valid human user.");

            return;
        }

        // Zamezíme tomu, aby bot posílal žádost sám sobě
        if ($user->id !== $recipientId) {
            $this->sendFriendRequestAction->execute($user->id, $recipientId);
        }
    }
}
