<?php

namespace App\Http\Controllers;

use App\Actions\SendMessageAction;
use App\Jobs\HandleAgentResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    /**
     * Načte historii zpráv z AI konverzací pro přihlášeného uživatele.
     * Využívá JOIN na tabulku uživatelů, aby získal reálná data o botech (jméno, avatar).
     */
    public function index()
    {
        $userId = auth()->id(); // ID 1 (Recruiter)

        $messages = DB::table('agent_conversation_messages as m')
            ->join('agent_conversations as c', 'm.conversation_id', '=', 'c.id')
            ->join('users as u', 'c.user_id', '=', 'u.id') // c.user_id drží ID bota
            ->where('m.user_id', $userId) // Zprávy doručené/přístupné pro recruitera
            ->orderBy('m.created_at', 'desc')
            ->select([
                'm.id',
                'm.conversation_id',
                'm.agent',
                'm.content as text',
                'm.created_at',
                'm.role',
                'u.name as bot_real_name', // Získáme jméno přímo z DB seederu bota!
            ])
            ->get()
            ->map(function ($msg) {
                // Vyčištění názvu třídy agenta (např. App\Ai\Agents\SentinelAgent -> SentinelAgent)
                $msg->agent_name = $msg->agent ? str_replace('App\\Ai\\Agents\\', '', $msg->agent) : null;

                if ($msg->role === 'user') {
                    $msg->sender = 'YOU';
                } else {
                    // Žádné nebezpečné hledání textu – prostě dosadíme reálné jméno bota z DB (VECTRA_CORE, SENTINEL_01...)
                    $msg->sender = $msg->bot_real_name ?? 'SYSTEM_BOT';
                }

                $msg->time = Carbon::parse($msg->created_at)->toTimeString();
                $msg->created_at = Carbon::parse($msg->created_at)->toIso8601String();
                $msg->read = true;

                // Odstraníme pomocný sloupec, ať neposíláme zbytečná data do frontendu
                unset($msg->bot_real_name);

                return $msg;
            });

        return response()->json($messages);
    }

    /**
     * Uloží odpověď uživatele do databáze pomocí centralizované SendMessageAction.
     */
    public function store(Request $request, SendMessageAction $sendMessageAction)
    {
        $request->validate([
            'message_id' => 'required|string',
            'text' => 'required|string|max:2000',
        ]);

        $userId = auth()->id();

        // 1. Najdeme původní zprávu, na kterou uživatel odpovídá
        $originalMessage = DB::table('agent_conversation_messages')
            ->where('id', $request->message_id)
            ->where('user_id', $userId)
            ->first();

        if (! $originalMessage) {
            return response()->json(['error' => 'ORIGINAL_NODE_NOT_FOUND'], 404);
        }

        // 2. Vytáhneme konverzaci, abychom zjistili, kterému botovi (recipient_id) odepisujeme
        $conversation = DB::table('agent_conversations')
            ->where('id', $originalMessage->conversation_id)
            ->first();

        if (! $conversation) {
            return response()->json(['error' => 'CONVERSATION_GRID_NOT_FOUND'], 404);
        }

        $botId = (int) $conversation->user_id; // ID bota (např. ID pro VECTRA_CORE)

        // 3. Použijeme injektovanou instanci akce ze service containeru
        // FIX: Místo null předáváme $originalMessage->agent, abychom splnili NOT NULL podmínku v SQLite
        $newMessageId = $sendMessageAction->execute(
            $userId,
            $botId,
            $request->text,
            $originalMessage->agent,
            'user'
        );

        $now = now();

        // 4. Spustíme na pozadí Job, který nechá AI bota zareagovat
        HandleAgentResponse::dispatch($userId, $conversation->id);

        // 5. Vrátíme čistou odpověď pro Piniu
        return response()->json([
            'id' => $newMessageId,
            'conversation_id' => $conversation->id,
            'agent' => $originalMessage->agent,
            'agent_name' => $originalMessage->agent ? str_replace('App\\Ai\\Agents\\', '', $originalMessage->agent) : null,
            'sender' => 'YOU',
            'text' => $request->text,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => true,
            'role' => 'user',
        ]);
    }

    /**
     * Odstraní zprávu a celé vlákno z databáze.
     */
    public function destroy($conversationId)
    {
        // Bezpečnostní ověření existence konverzace
        $conversationExists = DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->exists();

        if (! $conversationExists) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        // 1. Smažeme všechny zprávy propojené s touto konverzací
        DB::table('agent_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->delete();

        // 2. Smažeme samotnou konverzaci z hlavní tabulky konverzací
        DB::table('agent_conversations')
            ->where('id', $conversationId)
            ->delete();

        return response()->json(['status' => 'NODE_PURGED']);
    }
}
