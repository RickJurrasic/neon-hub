<?php

namespace App\Services;

use App\Events\PostLiked;
use App\Models\Post;

class LikeService
{
    public function like(Post $post): array
    {
        $post->likes()->firstOrCreate(['user_id' => auth()->id()]);
        $likesCount = $post->likes()->count();

        $this->notifyAndBroadcast($post, $likesCount, true);

        return [
            'likes_count' => $likesCount,
            'is_liked' => true,
        ];
    }

    public function unlike(Post $post): array
    {
        $post->likes()->where('user_id', auth()->id())->delete();
        $likesCount = $post->likes()->count();

        $this->notifyAndBroadcast($post, $likesCount, false);

        return [
            'likes_count' => $likesCount,
            'is_liked' => false,
        ];
    }

    private function notifyAndBroadcast(Post $post, int $likesCount, bool $isLiked): void
    {
        event(new PostLiked(
            $post->id,
            $likesCount,
            auth()->id(),
            auth()->user()?->name,
            $isLiked
        ));
    }
}