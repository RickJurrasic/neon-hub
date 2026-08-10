<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro vytvořený komentář.
     *
     * @param  array<string, mixed>  $comment
     */
    public function __construct(
        public readonly int $postId,
        public readonly array $comment,
        public readonly int $postOwnerId,
        public readonly int $userId,
    ) {}

    /**
     * Data předávaná na frontend přes WebSocket.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'postId' => $this->postId,
            'comment' => $this->comment,
            'postOwnerId' => $this->postOwnerId,
            'userId' => $this->userId,
        ];
    }

    /**
     * Kanály, na kterých se událost vysílá.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('posts')];
    }

    /**
     * Název události pro frontend listener (Laravel Echo).
     */
    public function broadcastAs(): string
    {
        return 'CommentCreated';
    }
}
