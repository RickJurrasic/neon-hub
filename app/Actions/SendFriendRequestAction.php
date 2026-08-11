<?php

namespace App\Actions;

use App\Events\FriendRequestReceived;
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

        // 3. Načtení odesílatele
        /** @var User $sender */
        $sender = User::findOrFail($senderId);

        // 4. Odeslání eventu se strukturou, která přesně odpovídá NeonHubService
        event(new FriendRequestReceived(
            $recipientId,
            [
                'id' => $friendship->id,             // ID přátelství (klíčové pro PATCH/DELETE)
                'user_id' => $sender->id,            // ID uživatele (klíčové pro profil)
                'name' => $sender->name,
                'role' => $sender->role ?? 'EXTERNAL_NODE',
                'bio' => $sender->bio ?? '"Šifrované bio prázdné."',
                'trust_level' => $sender->trust_level ?? 50,
                'latency' => $sender->latency ?? '24ms_STABLE',
                'avatar' => $sender->avatar_url,
                'status' => $friendship->status,
            ]
        ));

        return $friendship;
    }
}