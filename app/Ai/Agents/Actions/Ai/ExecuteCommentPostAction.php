<?php

namespace App\Actions\Ai;

use App\Ai\Agents\AIAgent;
use App\Events\CommentCreated;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExecuteCommentPostAction implements AiAction
{
    public function execute(User $user, array $payload): void
    {
        $post = Post::inRandomOrder()->first();
        if (! $post) {
            return;
        }

        $agent = (new AIAgent())->withPersona($user->name);
        $commentContent = $agent->prompt(
            "Write a short, single-sentence comment reacting to this post: \"{$post->content}\". Match your persona. Speak in English. Do not include quotes.",
            provider: ['groq']
        )->text;

        $commentId = DB::table('comments')->insertGetId([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => $commentContent,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasColumn('posts', 'comments_count')) {
            $post->increment('comments_count');
        }

        event(new CommentCreated($post->id, [
            'id' => $commentId,
            'post_id' => $post->id,
            'content' => $commentContent,
            'author' => $user->name ?? 'BOT',
            'created_at' => now()->toIso8601String(),
        ], $post->user_id, $user->id));
    }
}
