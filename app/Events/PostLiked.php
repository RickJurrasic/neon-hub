<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $postId,
        public int $likesCount,
        public ?int $userId,
        public ?string $userName,
        public bool $isLiked,
    ) {
    }

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

    public function broadcastOn(): array
    {
        return [new Channel('posts')];
    }

    public function broadcastAs(): string
    {
        return 'PostLiked';
    }
}
