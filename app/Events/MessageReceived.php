<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro přijatou zprávu v chatu.
     *
     * @param array<string, mixed> $data Data o zprávě (id, text, conversation_id, sender_id atd.).
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $data
    ) {}

    /**
     * Definice dat, která poletí přes WebSocket na frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->data,
        ];
    }

    /**
     * Získat privátní kanál příjemce zprávy.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
        ];
    }

    /**
     * Název události pro WebSocket listener na frontendu (Echo.private(...).listen('.MessageReceived')).
     */
    public function broadcastAs(): string
    {
        return 'MessageReceived';
    }
}