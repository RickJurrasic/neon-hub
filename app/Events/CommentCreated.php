<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $postId,
        public array $comment,
        public int $postOwnerId,
        public int $userId,
    ) {
    }

    public function broadcastWith(): array
    {
        return [
            'postId' => $this->postId,
            'comment' => $this->comment,
            'postOwnerId' => $this->postOwnerId,
            'userId' => $this->userId,
        ];
    }

    public function broadcastOn(): array
    {
        return [new Channel('posts')];
    }

    public function broadcastAs(): string
    {
        return 'CommentCreated';
    }
}
