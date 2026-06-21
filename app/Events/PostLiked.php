<?php

namespace App\Events;

use App\Models\Post;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $postId;

    public int $likesCount;

    public ?int $userId;

    public ?string $userName;

    public bool $isLiked;

    /**
     * Robustní konstruktor, který zvládne volání z Both a Controlleru.
     *
     * @param  Post|int  $post  Post objekt nebo ID příspěvku
     * @param  int  $likesCount  Počet lajků
     * @param  User|int|null  $user  Uživatel (objekt, ID nebo null pro guest)
     * @param  bool  $isLiked  Zda jde o like (true) nebo unlike (false)
     */
    public function __construct($post, int $likesCount, $user = null, bool $isLiked = true)
    {
        // Pokud přijde celý objekt Post, vezmi id, jinak to ber jako přímé ID
        $this->postId = $post instanceof Post ? $post->id : (int) $post;

        $this->likesCount = $likesCount;

        $this->isLiked = $isLiked;

        // Flexibilní zpracování uživatele
        if ($user instanceof User) {
            $this->userId = $user->id;
            $this->userName = $user->name;
        } elseif (is_int($user) || is_numeric($user)) {
            $this->userId = (int) $user;
            $this->userName = null;
        } else {
            // Výchozí hodnoty pro guest (není přihlášen)
            $this->userId = null;
            $this->userName = null;
        }
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('posts'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PostLiked';
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
}
