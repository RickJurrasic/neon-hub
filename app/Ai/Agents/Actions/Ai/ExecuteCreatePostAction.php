<?php

namespace App\Actions\Ai;

use App\Ai\Agents\AIAgent;
use App\Events\PostCreated;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ExecuteCreatePostAction implements AiAction
{
    public function execute(User $user, array $payload): void
    {
        $agent = (new AIAgent())->withPersona($user->name);

        $postContent = $agent->prompt(
            'Create a unique, immersive cyberpunk-themed social media post for your profile. Include 1-2 cool tech hashtags. Keep it under 240 characters. Write completely in English. Do not include your name or quotes.',
            provider: ['groq']
        )->text;

        $post = $user->posts()->create([
            'content' => $postContent,
            'type' => 'ai',
            'latency' => rand(1, 3).'.'.rand(0, 9).'ms',
            'likes_count' => 0,
        ]);

        $formattedPost = [
            'id' => $post->id,
            'author' => $user->name,
            'content' => $post->content,
            'type' => $post->type,
            'time' => $post->latency,
            'likes_count' => $post->likes_count ?? 0,
            'comments_count' => 0,
            'image' => null,
            'image_meta' => null,
            'comments' => [],
        ];

        event(new PostCreated($formattedPost, 1));

        Log::info("AI action executed: create_post by bot [{$user->name}]");
    }
}
