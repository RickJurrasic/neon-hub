<?php

namespace App\Ai\Agents\Actions\Ai;

use App\Ai\Agents\Actions\AIAction;
use App\Events\PostLiked;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExecuteLikePostAction implements AIAction
{
    public function execute(User $user, array $payload): void
    {
        // 1. Získáme příspěvek z payloadu nebo vybereme náhodný
        $postId = $payload['post_id'] ?? null;
        $demoOwnerId = $payload['demo_owner_id'] ?? null;

        if ($postId) {
            /** @var Post|null $post */
            $post = Post::find($postId);
            // Ensure post belongs to the session owner
            if ($post && $demoOwnerId && $post->demo_owner_id !== $demoOwnerId) {
                $post = null;
            }
        } else {
            /** @var Post|null $post */
            $post = $demoOwnerId
                ? Post::where('demo_owner_id', $demoOwnerId)->inRandomOrder()->first()
                : Post::inRandomOrder()->first();
        }

        if (! $post) {
            return;
        }

        // 2. Kontrola, zda už uživatel příspěvek nelajknul
        $alreadyLiked = DB::table('likes')
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->exists();

        if ($alreadyLiked) {
            return;
        }

        // 3. Vytvoříme záznam o lajku
        DB::table('likes')->insert([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Inkrementace počítadla bez zbytečných DB schema dotazů
        if (array_key_exists('likes_count', $post->getAttributes())) {
            $post->increment('likes_count');
        } elseif (array_key_exists('likes', $post->getAttributes())) {
            $post->increment('likes');
        }

        /** @var Post $freshPost */
        $freshPost = $post->fresh() ?? $post;
        $likesCount = $freshPost->likes_count ?? $freshPost->likes()->count();
        $userName = $user->name ?? 'BOT';

        // 4. Odbavíme event pro WebSocket
        $postOwnerId = (int) ($post->demo_owner_id ?? $post->user_id);
        event(new PostLiked($post->id, (int) $likesCount, $user->id, $userName, true, $postOwnerId));

        // 5. Vytvoříme notifikaci autorovi příspěvku (pokud to není sám bot)
        if ($postOwnerId !== $user->id) {
            DB::table('notifications')->insert([
                'id' => Str::uuid(),
                'type' => 'App\Notifications\PostLiked',
                'notifiable_id' => $postOwnerId,
                'notifiable_type' => User::class,
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
}
