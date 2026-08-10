<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Vytvoří novou instanci události pro lajknutí příspěvku.
     */
    public function __construct(
        public readonly int $postId,
        public readonly int $likesCount,
        public readonly ?int $userId,
        public readonly ?string $userName,
        public readonly bool $isLiked,
    ) {}

    /**
     * Payload předávaný přes WebSocket na frontend.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'postId' => $this->postId,
            'likesCount' => $this->likesCount,
            'userId' => $this->userId,
            'userName' => $this->userName,
            'isLiked' => $this->isLiked,
        ];
    }

    /**
     * Kanály pro broadcasting.
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
        return 'PostLiked';
    }
}
