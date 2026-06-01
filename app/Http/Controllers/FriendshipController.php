<?php

namespace App\Http\Controllers;

use App\Actions\SendFriendRequestAction;
use App\Events\FriendRequestReceived;
use App\Models\Friendship;
use App\Models\User; // <--- Importujeme tvůj event
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function store(Request $request, SendFriendRequestAction $action)
    {
        // 1. Validace recipient_id
        $validated = $request->validate(['recipient_id' => 'required|exists:users,id']);
        $authId = auth()->id();
        $recipientId = $validated['recipient_id'];

        // 2. Spuštění tvé Action logiky (vytvoří záznam v DB)
        $action->execute($authId, $recipientId);

        // 3. Vytáhneme čerstvý záznam z DB
        $friendship = Friendship::where('sender_id', $authId)
            ->where('recipient_id', $recipientId)
            ->where('status', 'pending')
            ->first();

        if (! $friendship) {
            return response()->json(['status' => 'error', 'message' => 'Record not found'], 404);
        }

        // 4. DATA PRO PŘÍJEMCE (Real-time přes Echo):
        // Příjemce potřebuje vidět data ODESÍLATELE (tebe), aby viděl tvůj avatar!
        $sender = auth()->user();
        $dataForRecipient = [
            'id' => $friendship->id,
            'user_id' => $sender->id,
            'name' => $sender->name,
            'role' => $sender->role ?? 'EXTERNAL_NODE',
            'bio' => $sender->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $sender->trust_level ?? 50,
            'latency' => $sender->latency ?? '24ms_STABLE',
            'avatar' => $sender->avatar_url,
            'status' => 'pending',
        ];

        // Manuálně odpálíme tvůj event s kompletními daty odesílatele včetně AVATARU
        // (Pokud ho odpaluješ i v Action, tamto volání eventu v Action smaž, nebo ho nech tady, ať se to nedubluje)
        broadcast(new FriendRequestReceived($recipientId, $dataForRecipient))->toOthers();

        // 5. DATA PRO ODESÍLATELE (Odpověď na Axios request):
        // Ty jako odesílatel potřebuješ v loutkovém stavu vidět data příjemce
        $recipient = User::find($recipientId);
        $dataForSender = [
            'id' => $friendship->id,
            'user_id' => $recipient->id,
            'name' => $recipient->name,
            'role' => $recipient->role ?? 'EXTERNAL_NODE',
            'bio' => $recipient->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $recipient->trust_level ?? 50,
            'latency' => $recipient->latency ?? '24ms_STABLE',
            'avatar' => $recipient->avatar_url,
            'status' => 'pending',
        ];

        return response()->json([
            'status' => 'Request sent',
            'friendship' => $dataForSender,
        ]);
    }

    public function update(Request $request, $id)
    {
        $friendship = Friendship::findOrFail($id);
        $friendship->update(['status' => 'accepted']);

        // Vytáhneme data o příteli, abychom je mohli poslat zpět do frontendu k okamžitému přesunu do "friends"
        $authId = auth()->id();
        $friendId = $friendship->sender_id == $authId ? $friendship->recipient_id : $friendship->sender_id;
        $friend = User::find($friendId);

        $neonFriendData = [
            'id' => $friendship->id,
            'user_id' => $friend->id,
            'name' => $friend->name,
            'role' => $friend->role ?? 'EXTERNAL_NODE',
            'bio' => $friend->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $friend->trust_level ?? 50,
            'latency' => $friend->latency ?? '24ms_STABLE',
            'avatar' => $friend->avatar_url,
            'status' => 'accepted',
        ];

        return response()->json([
            'status' => 'success',
            'friend' => $neonFriendData,
        ]);
    }

    public function destroy($id)
    {
        $friendship = Friendship::findOrFail($id);
        $friendship->delete();

        return response()->json([
            'message' => 'Request declined',
            'id' => $id,
        ]);
    }
}
