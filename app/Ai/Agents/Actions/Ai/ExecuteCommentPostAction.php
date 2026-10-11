<?php

namespace App\Ai\Agents\Actions\Ai;

use App\Ai\Agents\Actions\AIAction;
use App\Ai\Agents\AIAgent;
use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\ActiveDemoUsers;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExecuteCommentPostAction implements AIAction
{
    public function execute(User $user, array $payload): void
    {
        // 1. Pokud je v payloadu post_id, použijeme ho, jinak vybereme náhodný
        // Comments are allowed only for an active demo session.
        $demoOwnerId = (int) ($payload['demo_owner_id'] ?? 0);

        if (! ActiveDemoUsers::isActiveSession($demoOwnerId)) {
            Log::info('AI comment skipped: demo session is not active.', [
                'demo_owner_id' => $demoOwnerId ?: null,
                'actor_user_id' => $user->id,
            ]);

            return;
        }

        // Restrict the target post to the current demo session.
        $postId = $payload['post_id'] ?? null;

        $postQuery = Post::query()
            ->where('demo_owner_id', $demoOwnerId);

        $post = $postId
            ? $postQuery->whereKey((int) $postId)->first()
            : $postQuery->inRandomOrder()->first();

        if (! $post) {
            Log::info('AI comment skipped: no post belongs to the active demo session.', [
                'demo_owner_id' => $demoOwnerId,
                'actor_user_id' => $user->id,
            ]);

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

        // The AI request may take long enough for the session to expire.
        if (! ActiveDemoUsers::isActiveSession($demoOwnerId)) {
            Log::info('AI comment skipped: demo session expired during generation.', [
                'demo_owner_id' => $demoOwnerId,
                'actor_user_id' => $user->id,
            ]);

            return;
        }

        // Ensure the target post still exists and belongs to this session.
        $post = Post::query()
            ->whereKey($post->id)
            ->where('demo_owner_id', $demoOwnerId)
            ->first();

        if (! $post) {
            Log::info('AI comment skipped: target post no longer exists for this session.', [
                'demo_owner_id' => $demoOwnerId,
                'actor_user_id' => $user->id,
            ]);

            return;
        }

        // 3. Uložíme komentář přes Eloquent relaci
        /** @var Comment $comment */
        $comment = $post->comments()->create([
            'user_id' => $user->id,
            'content' => $commentContent,
            'demo_owner_id' => $demoOwnerId,
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
