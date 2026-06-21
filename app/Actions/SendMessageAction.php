<?php

namespace App\Actions;

use App\Events\MessageReceived;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SendMessageAction
{
    /**
     * @param  int  $senderId  Kdo zprávu skutečně odesílá (Bot ID nebo Uživatel ID 1)
     * @param  int  $recipientId  Komu zpráva směřuje (zde se dynamicky dopočítá)
     * @param  string  $content  Obsah zprávy
     * @param  string|null  $agentName  Jméno AI bota nebo plný název třídy agenta
     * @param  string  $role  'assistant' (bot) nebo 'user' (ty jako recruiter)
     */
    public function execute(int $senderId, int $recipientId, string $content, ?string $agentName = null, string $role = 'assistant'): string
    {
        $now = now();
        $recruiterId = 1;

        // 🌌 Dynamické určení, pod kterým Bot ID je konverzace vedena.
        // Konverzace v 'agent_conversations' jsou indexovány podle ID bota (sloupec user_id).
        $botId = ($role === 'assistant') ? $senderId : $recipientId;

        // Najdeme nebo vytvoříme konverzaci svázanou s daným botem
        $conversation = DB::table('agent_conversations')
            ->where('user_id', $botId)
            ->first();

        if (! $conversation) {
            $conversationId = (string) Str::uuid();
            DB::table('agent_conversations')->insert([
                'id' => $conversationId,
                'user_id' => $botId,
                'title' => 'SECURE_CHANNEL',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $conversationId = $conversation->id;
        }

        $messageId = (string) Str::uuid();

        // 🤖 FIX: Sestavení nebo převzetí názvu třídy agenta. V DB je NOT NULL, musí ho mít i zprávy od uživatele!
        $agentClass = null;

        if ($agentName) {
            if (str_starts_with($agentName, 'App\\')) {
                // Pokud z controlleru přišel rovnou plný FQCN namespace (např. z $originalMessage->agent)
                $agentClass = $agentName;
            } else {
                // Pokud přišel pouze krátký string (např. 'Sentinel' nebo 'SENTINEL_01')
                $agentClass = 'App\\Ai\\Agents\\'.str_replace('_', '', $agentName).'Agent';
            }
        } else {
            // Pojistka: Pokud by agentName nepřišel vůbec, vytáhneme si třídu z předchozích zpráv v tomto vlákně
            $agentClass = DB::table('agent_conversation_messages')
                ->where('conversation_id', $conversationId)
                ->whereNotNull('agent')
                ->value('agent');
        }

        // Uložíme zprávu do agent_conversation_messages s dynamickou rolí!
        DB::table('agent_conversation_messages')->insert([
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'user_id' => $recruiterId, // Vlákno stále patří pod přehled recruitera
            'agent' => $agentClass,
            'role' => $role,           // <--- ZDE SE UŽ UKLÁDÁ SKUTEČNÁ ROLE ('user' nebo 'assistant')
            'content' => $content,
            'attachments' => '[]',
            'tool_calls' => '[]',
            'tool_results' => '[]',
            'usage' => '[]',
            'meta' => '[]',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Vyčištěný název pro frontend (např. SentinelAgent)
        $cleanAgentName = $agentClass ? str_replace('App\\Ai\\Agents\\', '', $agentClass) : null;

        // Určení odesílatele pro frontend a websockety
        $sender = ($role === 'user') ? 'YOU' : ($cleanAgentName ?? 'BOT');

        // Odpálíme event pro realtime broadcast (vždy posíláme na kanál recruitera - ID 1)
        event(new MessageReceived($recruiterId, [
            'id' => $messageId,
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'agent' => $agentClass,
            'agent_name' => ($role === 'assistant') ? $cleanAgentName : null, // 🔥 FIX: Pro 'user' to musí jít ven jako null!
            'sender' => $sender,
            'text' => $content,
            'time' => $now->toTimeString(),
            'created_at' => $now->toIso8601String(),
            'read' => false,
            'role' => $role,
        ]));

        return $messageId;
    }
}
