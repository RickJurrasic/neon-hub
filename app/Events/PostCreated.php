<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // <--- DŮLEŽITÉ rozhraní
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $post;

    public int $userId;

    /**
     * Předáme zformátovaný post a ID uživatele, kterému má Reverb zprávu doručit.
     */
    public function __construct(array $post, int $userId)
    {
        $this->post = $post;
        $this->userId = $userId;
    }

    /**
     * Kanál se musí přesně shodovat s tím, co ti poslouchá Vue v useNotificationStore.js
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
        ];
    }

    /**
     * Název události, kterou Echo odchytává v metodě .listen()
     */
    public function broadcastAs(): string
    {
        return 'PostCreated';
    }
}
