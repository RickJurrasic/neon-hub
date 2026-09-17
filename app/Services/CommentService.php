<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Events\NewActivityAlert;
use App\Models\Comment;
use App\Models\Post;

class CommentService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createComment(Post $post, array $data): array
    {
        $comment = $post->comments()->create([
            'user_id' => (int) auth()->id(),
            'content' => $data['content'],
            'demo_owner_id' => $post->demo_owner_id ?? (int) auth()->id(),
        ]);

        $commentData = $this->formatCommentData($comment, $post);

        $this->notifyAndBroadcast($post, $commentData);

        return $commentData;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        $comment->update([
            'content' => $data['content'],
        ]);

        return $comment;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCommentData(Comment $comment, Post $post): array
    {
        $user = auth()->user();
        $createdAt = $comment->created_at ?? now();

        return [
            'id' => $comment->id,
            'post_id' => $post->id,
            'content' => $comment->content,
            'author' => $user ? $user->name : 'Anonymous',
            'timestamp' => $createdAt->format('H:i'),
            'created_at' => $createdAt->toIso8601String(),
            'demo_owner_id' => $comment->demo_owner_id,
        ];
    }

    /**
     * @param  array<string, mixed>  $commentData
     */
    private function notifyAndBroadcast(Post $post, array $commentData): void
    {
        $userId = (int) auth()->id();
        $user = auth()->user();
        $recipientId = $post->demo_owner_id ?? $post->user_id;

        event(new CommentCreated($post->id, $commentData, $post->user_id, $userId));

        if ($recipientId !== $userId) {
            $userName = $user ? $user->name : 'Someone';
            event(new NewActivityAlert(
                $recipientId,
                $userName.' commented on your post.'
            ));
        }
    }
}
