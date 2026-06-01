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

        // 3. Vytáhneme si kompletního odesílatele (bota) i s jeho sloupci z DB
        $sender = User::find($senderId);

        // 4. Odpálíme event se VŠEMI daty, které Pinia a profil potřebují k instantnímu vykreslení
        event(new FriendRequestReceived($recipientId, [
            'id' => $friendship->id,
            'user_id' => $senderId, // useNotificationStore.js to mapuje přes user_id
            'name' => $sender ? $sender->name : 'UNKNOWN_ENTITY',
            'role' => $sender->role ?? 'DEFENSE', // Fallback, pokud by v DB chybělo
            'bio' => $sender->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $sender->trust_level ?? 88,
            'latency' => $sender->latency ?? '12ms_STABLE',
            'avatar' => $sender ? $sender->avatar_url : null, // TADY JE TEN CHYBĚJÍCÍ AVATAR!
            'status' => 'pending',
        ]));

        return $friendship;
    }
}
