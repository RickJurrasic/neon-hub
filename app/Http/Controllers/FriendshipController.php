<?php

namespace App\Http\Controllers;

use App\Actions\SendFriendRequestAction;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function store(Request $request, SendFriendRequestAction $action)
    {
        $validated = $request->validate(['recipient_id' => 'required|exists:users,id']);
        $recipientId = $validated['recipient_id'];

        $friendship = $action->execute(auth()->id(), $recipientId);

        if (! $friendship) {
            return response()->json(['status' => 'error', 'message' => 'Žádost již existuje.'], 422);
        }

        $recipient = User::find($recipientId);

        return response()->json([
            'status' => 'Request sent',
            'friendship' => $this->formatFriendResponse($friendship->id, $recipient, 'pending'),
        ]);
    }

    public function update($id)
    {
        $friendship = Friendship::findOrFail($id);
        $friendship->update(['status' => 'accepted']);

        $authId = auth()->id();
        $friendId = $friendship->sender_id === $authId ? $friendship->recipient_id : $friendship->sender_id;
        $friend = User::find($friendId);

        return response()->json([
            'status' => 'success',
            'friend' => $this->formatFriendResponse($friendship->id, $friend, 'accepted'),
        ]);
    }

    public function destroy($id)
    {
        Friendship::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Request declined',
            'id' => $id,
        ]);
    }

    private function formatFriendResponse(int $friendshipId, User $user, string $status): array
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
