<?php

namespace App\Http\Controllers;

use App\Jobs\HandleAgentResponse;
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
            // FIX: Vybíráme čistý 'created_at' místo 'created_at as time'
            ->select('id', 'conversation_id', 'agent', 'content as text', 'created_at', 'role')
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

                // FIX: 'time' bude sloužit pro UI výpis, 'created_at' pro přesný sort ve Vue
                $msg->time = Carbon::parse($msg->created_at)->toTimeString();
                $msg->created_at = Carbon::parse($msg->created_at)->toIso8601String();
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
        $now = now();

        DB::table('agent_conversation_messages')->insert([
            'id' => $newMessageId,
            'conversation_id' => $originalMessage->conversation_id,
            'user_id' => $userId,
            'agent' => $originalMessage->agent,
            'role' => 'user',
            'content' => $request->text,
            'attachments' => '[]', 'tool_calls' => '[]', 'tool_results' => '[]', 'usage' => '[]', 'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // FIX: Použití správných proměnných, které v této metodě reálně existují
        HandleAgentResponse::dispatch($userId, $originalMessage->conversation_id);

        return response()->json([
            'id' => $newMessageId,
            'conversation_id' => $originalMessage->conversation_id,
            'agent' => $originalMessage->agent,
            'agent_name' => str_replace('App\\Ai\\Agents\\', '', $originalMessage->agent),
            'sender' => 'YOU',
            'text' => $request->text, // FIX: Musíme poslat text i zpátky frontendu, aby ho Pinia mohla vykreslit!
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => true,
            'role' => 'user',
        ]);
    }

    /**
     * Odstraní zprávu z databáze.
     */
    public function destroy($conversationId)
    {
        $userId = auth()->id();

        // 1. Bezpečnostní pojistka: Ověříme, že konverzace vůbec existuje a patří přihlášenému uživateli
        // Předpokládám, že tabulka se jmenuje 'agent_conversations' podle tvých schémat
        $conversationExists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->exists();

        if (! $conversationExists) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        // 2. Smažeme všechny zprávy propojené s touto konverzací
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->where('user_id', $userId) // Pojistka
            ->delete();

        // 3. Smažeme samotnou konverzaci z hlavní tabulky konverzací
        DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['status' => 'NODE_PURGED']);
    }
}
