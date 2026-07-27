<?php

namespace App\Http\Controllers;

use App\Events\PostLiked;
use App\Models\Post;

class LikeController extends Controller
{
    public function store(Post $post)
    {
        // firstOrCreate zabrání duplicitám na úrovni aplikace
        $post->likes()->firstOrCreate(['user_id' => auth()->id()]);
        $likesCount = $post->likes()->count();

        // Volání privátní metody pro zachování konzistence
        $this->notifyAndBroadcast($post, $likesCount, true);

        return response()->json([
            'likes_count' => $likesCount,
            'is_liked' => true,
        ]);
    }

    public function destroy(Post $post)
    {
        $post->likes()->where('user_id', auth()->id())->delete();
        $likesCount = $post->likes()->count();

        // Volání privátní metody pro zachování konzistence
        $this->notifyAndBroadcast($post, $likesCount, false);

        return response()->json([
            'likes_count' => $likesCount,
            'is_liked' => false,
        ]);
    }

    /**
     * Zpracuje real-time broadcast pro lajky.
     */
    private function notifyAndBroadcast(Post $post, int $likesCount, bool $isLiked): void
    {
        event(new PostLiked(
            $post->id,
            $likesCount,
            auth()->id(),
            auth()->user()?->name, // OPRAVENO: Předáváme string (jméno), ne celý objekt
            $isLiked
        ));
    }
}
