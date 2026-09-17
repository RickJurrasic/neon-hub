<?php

namespace App\Ai\Agents\Actions\Ai;

use App\Ai\Agents\Actions\AIAction;
use App\Ai\Agents\AIAgent;
use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class ExecuteCommentPostAction implements AIAction
{
    public function execute(User $user, array $payload): void
    {
        // 1. Pokud je v payloadu post_id, použijeme ho, jinak vybereme náhodný
        $postId = $payload['post_id'] ?? null;

        /** @var Post|null $post */
        $post = $postId
            ? Post::find($postId)
            : Post::inRandomOrder()->first();

        if (! $post) {
            return;
        }

        // 2. Vygenerujeme komentář pomocí AI
        $agent = (new AIAgent())->withPersona($user->name);

        $response = $agent->prompt(
            "Write a short, single-sentence comment reacting to this post: \"{$post->content}\". Match your persona. Speak in English. Do not include quotes.",
            provider: ['groq']
        );

        // Očistíme text od případných nechtěných uvozovek nebo mezer
        $commentContent = Str::of($response->text ?? '')
            ->trim()
            ->replace(['"', "'"], '')
            ->toString();

        if (empty($commentContent)) {
            return;
        }

        // 3. Uložíme komentář přes Eloquent relaci
        /** @var Comment $comment */
        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'content' => $commentContent,
        ]);

        // Inkrementujeme počet komentářů, pokud sloupec v modelu existuje
        if (array_key_exists('comments_count', $post->getAttributes())) {
            $post->increment('comments_count');
        }

        // 4. Odbavíme událost pro frontend (Reverb WebSocket)
        event(new CommentCreated($post->id, [
            'id' => $comment->id,
            'post_id' => $post->id,
            'content' => $comment->content,
            'author' => $user->name ?? 'BOT',
            'created_at' => $comment->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'demo_owner_id' => $post->demo_owner_id,
        ], $post->user_id, $user->id));
    }
}
