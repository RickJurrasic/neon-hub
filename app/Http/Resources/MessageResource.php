<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'agent' => $this->agent,
            'agent_name' => $this->agent ? str_replace('App\\Ai\\Agents\\', '', $this->agent) : null,
            'text' => $this->text,
            'sender' => $this->determineSender(),
            'time' => $this->created_at?->toTimeString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'read' => true,
            'role' => $this->role,
        ];
    }

    private function determineSender(): string
    {
        if ($this->role === 'user') {
            return 'YOU';
        }

        return $this->bot_real_name ?? 'SYSTEM_BOT';
    }
}