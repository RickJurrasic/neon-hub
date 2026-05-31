<?php

namespace App\Actions;

use App\Events\FriendRequestReceived;
use App\Models\Friendship;
use App\Models\User;

class SendFriendRequestAction
{
    /**
     * @return Friendship|null Vrací Friendship nebo null, pokud už vazba existuje.
     */
    public function execute(int $senderId, int $recipientId): ?Friendship
    {
        // 1. Zkontrolujeme, zda už mezi nimi neexistuje žádný záznam
        $existing = Friendship::where(function ($query) use ($senderId, $recipientId) {
            $query->where('sender_id', $senderId)->where('recipient_id', $recipientId);
        })->orWhere(function ($query) use ($senderId, $recipientId) {
            $query->where('sender_id', $recipientId)->where('recipient_id', $senderId);
        })->first();

        // Pokud už nějaká vazba existuje, nebudeme vytvářet novou (zabráníme duplicitám)
        if ($existing) {
            return null;
        }

        // 2. Uložíme do DB, pokud vazba neexistuje
        $friendship = Friendship::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'status' => 'pending',
        ]);

        // 3. Vytáhneme si skutečné jméno odesílatele
        $sender = User::find($senderId);

        // 4. Odpálíme event se skutečnými daty
        event(new FriendRequestReceived($recipientId, [
            'id' => $friendship->id,
            'sender_id' => $senderId,
            'name' => $sender ? $sender->name : 'UNKNOWN_ENTITY',
            'status' => 'pending',
        ]));

        return $friendship;
    }
}
