<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'agent' => $this->agent,
            'agent_name' => $this->agent ? str_replace('App\\Ai\\Agents\\', '', $this->agent) : null,
            'text' => $this->text,
            'sender' => $this->determineSender(),
            'time' => Carbon::parse($this->created_at)->toTimeString(),
            'created_at' => Carbon::parse($this->created_at)->toIso8601String(),
            'read' => true,
            'role' => $this->role,
        ];
    }

    private function determineSender(): string
    {
        if ($this->role === 'user') {
            return 'YOU';
        }

        // Pokud používáme objekt, který nemá bot_real_name, fallback na SYSTEM_BOT
        return $this->bot_real_name ?? 'SYSTEM_BOT';
    }
}
