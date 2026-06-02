<?php

namespace App\Http\Controllers;

use App\Jobs\RespondToUserMessage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    /**
     * Načte historii zpráv z AI konverzací pro přihlášeného uživatele.
     */
    public function index()
    {
        $userId = auth()->id();

        $messages = DB::table('agent_conversation_messages')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            // PŘIDÁNO: conversation_id do selectu
            ->select('id', 'conversation_id', 'agent', 'content as text', 'created_at as time', 'role')
            ->get()
            ->map(function ($msg) {
                // Vypreparujeme čisté jméno agenta (např. SentinelAgent)
                $agentName = str_replace('App\\Ai\\Agents\\', '', $msg->agent);
                $msg->agent_name = $agentName;

                if ($msg->role === 'user') {
                    $msg->sender = 'YOU';
                } else {
                    $msg->sender = $agentName;
                }

                $msg->time = Carbon::parse($msg->time)->toTimeString();
                $msg->read = true;

                return $msg;
            });

        return response()->json($messages);
    }

    /**
     * Uloží odpověď uživatele do databáze k dané konverzaci.
     */
    public function store(Request $request)
    {
        $request->validate([
            'message_id' => 'required|string',
            'text' => 'required|string|max:2000',
        ]);

        $userId = auth()->id();

        $originalMessage = DB::table('agent_conversation_messages')
            ->where('id', $request->message_id)
            ->where('user_id', $userId)
            ->first();

        if (! $originalMessage) {
            return response()->json(['error' => 'ORIGINAL_NODE_NOT_FOUND'], 404);
        }

        $newMessageId = (string) Str::uuid();

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $originalMessage->conversation_id,
            'user_id' => $userId,
            'agent' => $originalMessage->agent,
            'role' => 'user',
            'content' => $request->text,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // =========================================================================
        // PŘIDÁNO: Spuštění automatické odpovědi bota na pozadí (Queue)
        // =========================================================================
        RespondToUserMessage::dispatch(
            $userId,
            $originalMessage->conversation_id
        );
        // =========================================================================

        return response()->json([
            'id' => $newMessageId,
            'conversation_id' => $originalMessage->conversation_id,
            'agent' => $originalMessage->agent,
            'agent_name' => str_replace('App\\Ai\\Agents\\', '', $originalMessage->agent),
            'sender' => 'YOU',
            'text' => $request->text,
            'time' => now()->toTimeString(),
            'read' => true,
            'role' => 'user',
        ]);
    }

    /**
     * Odstraní zprávu z databáze.
     */
    public function destroy($id)
    {
        DB::table('agent_conversation_messages')
            ->where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json(['status' => 'NODE_PURGED']);
    }

    public function markAsRead(Request $request)
    {
        return response()->json(['status' => 'success']);
    }
}
