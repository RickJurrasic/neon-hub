<?php

namespace App\Policies;

use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    public function update(User $user, Friendship $friendship): bool
    {
        // Přijmout žádost může pouze ten, komu byla adresována
        return $friendship->recipient_id === $user->id;
    }

    public function delete(User $user, Friendship $friendship): bool
    {
        // Zrušit/odmítnout může odesílatel i příjemce
        return $friendship->sender_id === $user->id || $friendship->recipient_id === $user->id;
    }
}