<?php

namespace App\Actions\Ai;

use App\Events\PostLiked;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ExecuteLikePostAction implements AiAction
{
    public function execute(User $user, array $payload): void
    {
        $post = Post::inRandomOrder()->first();
        if (! $post) {
            return;
        }

        $alreadyLiked = DB::table('likes')
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->exists();

        if ($alreadyLiked) {
            return;
        }

        DB::table('likes')->insert([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasColumn('posts', 'likes_count')) {
            $post->increment('likes_count');
        } elseif (Schema::hasColumn('posts', 'likes')) {
            $post->increment('likes');
        }

        $likesCount = $post->fresh()->likes_count ?? $post->likes()->count();
        $userName = $user->name ?? 'BOT';

        // Zde předáváme parametry přesně podle tvého zjednodušeného eventu!
        event(new PostLiked($post->id, (int) $likesCount, $user->id, $userName, true));

        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => 'App\\Notifications\\PostLiked',
            'notifiable_id' => $post->user_id,
            'notifiable_type' => 'App\\Models\\User',
            'data' => json_encode([
                'type' => 'like',
                'post_id' => $post->id,
                'user_id' => $user->id,
                'user_name' => $userName,
                'message' => $userName.' liked your post.',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
