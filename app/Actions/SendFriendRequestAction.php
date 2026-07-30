<?php

namespace App\Actions;

use App\Events\FriendRequestReceived;
use App\Http\Resources\UserResource;
use App\Models\Friendship;
use App\Models\User;

class SendFriendRequestAction
{
    /**
     * Vytvoří žádost o přátelství a informuje příjemce přes WebSocket.
     */
    public function execute(int $senderId, int $recipientId): ?Friendship
    {
        // 1. Rychlá kontrola existence bez načítání modelu z DB
        if (Friendship::between($senderId, $recipientId)->exists()) {
            return null;
        }

        // 2. Vytvoření žádosti
        $friendship = Friendship::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'status' => 'pending',
        ]);

        // 3. Načtení odesílatele a odbavení eventu přes UserResource
        $sender = User::findOrFail($senderId);

        event(new FriendRequestReceived(
            $recipientId,
            array_merge(
                (new UserResource($sender))->resolve(),
                ['friendship_id' => $friendship->id, 'status' => 'pending']
            )
        ));

        return $friendship;
    }
}