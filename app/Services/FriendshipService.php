<?php

namespace App\Services;

use App\Models\Friendship;
use App\Models\User;

class FriendshipService
{
    public function acceptFriendship(Friendship $friendship): array
    {
        $friendship->update(['status' => 'accepted']);

        $authId = auth()->id();
        $friendId = $friendship->sender_id === $authId ? $friendship->recipient_id : $friendship->sender_id;
        $friend = User::find($friendId);

        return [
            'status' => 'success',
            'friend' => $this->formatFriendResponse($friendship->id, $friend, 'accepted'),
        ];
    }

    public function deleteFriendship(Friendship $friendship): array
    {
        $id = $friendship->id;
        $friendship->delete();

        return [
            'message' => 'Request declined',
            'id' => $id,
        ];
    }

    public function formatFriendResponse(int $friendshipId, User $user, string $status): array
    {
        return [
            'id' => $friendshipId,
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $user->role ?? 'EXTERNAL_NODE',
            'bio' => $user->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $user->trust_level ?? 50,
            'latency' => $user->latency ?? '24ms_STABLE',
            'avatar' => $user->avatar_url,
            'status' => $status,
        ];
    }
}