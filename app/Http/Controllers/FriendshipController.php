<?php

namespace App\Http\Controllers;

use App\Actions\SendFriendRequestAction;
use App\Models\Friendship;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function store(Request $request, SendFriendRequestAction $action)
    {
        // Validace (předpokládáme, že posíláš recipient_id)
        $validated = $request->validate(['recipient_id' => 'required|exists:users,id']);

        // Zavoláme Action
        $action->execute(auth()->id(), $validated['recipient_id']);

        return response()->json(['status' => 'Request sent']);
    }

    public function update(Request $request, $id)
    {
        $friendship = Friendship::findOrFail($id);
        $friendship->update(['status' => 'accepted']);

        // Volitelně: pošli Event 'FriendshipAccepted', aby se UI v obou prohlížečích aktualizovalo
        return response()->json(['status' => 'success']);
    }

    public function destroy($id)
    {
        $friendship = Friendship::findOrFail($id);
        $friendship->delete();

        return response()->json(['message' => 'Request declined']);
    }
}
