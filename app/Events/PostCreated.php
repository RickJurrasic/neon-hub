<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro nový příspěvek.
     *
     * @param array<string, mixed> $post Zformátovaný příspěvek.
     * @param int $userId ID cílového uživatele pro privátní kanál.
     */
    public function __construct(
        public readonly array $post,
        public readonly int $userId
    ) {}

    /**
     * Kanály pro broadcasting (privátní kanál uživatele a veřejný feed).
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->userId),
            new Channel('posts'),
        ];
    }

    /**
     * Název události pro frontend listener (Laravel Echo).
     */
    public function broadcastAs(): string
    {
        return 'PostCreated';
    }

    /**
     * Payload předávaný přes WebSocket.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->post,
            'userId' => $this->userId,
        ];
    }
}