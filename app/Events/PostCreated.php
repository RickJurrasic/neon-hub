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
     * Předáme zformátovaný post a ID uživatele, kterému má Reverb zprávu doručit.
     */
    public function __construct(public array $post, public int $userId)
    {
    }

    /**
     * Kanál se musí přesně shodovat s tím, co ti poslouchá Vue v useNotificationStore.js
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userId),
            new Channel('posts'), // Veřejný kanal pro feed
        ];
    }

    /**
     * Název události, kterou Echo odchytává v metodě .listen()
     */
    public function broadcastAs(): string
    {
        return 'PostCreated';
    }

    /**
     * Broadcast payload s profilem (JSON serializace)
     */
    public function broadcastWith(): array
    {
        return [
            'data' => $this->post,
            'userId' => $this->userId,
        ];
    }
}
