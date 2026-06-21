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

        // Vyvoláme event s userem (pro notifikace i pro self)
        event(new PostLiked($post, $likesCount, auth()->user(), true));

        return response()->json([
            'likes_count' => $likesCount,
            'is_liked' => true,
        ]);
    }

    public function destroy(Post $post)
    {
        $post->likes()->where('user_id', auth()->id())->delete();
        $likesCount = $post->likes()->count();

        // Vyvoláme event s userem (pro notifikace i pro self)
        event(new PostLiked($post, $likesCount, auth()->user(), false));

        return response()->json([
            'likes_count' => $likesCount,
            'is_liked' => false,
        ]);
    }
}
