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
        ];
    }

    /**
     * @param  array<string, mixed>  $commentData
     */
    private function notifyAndBroadcast(Post $post, array $commentData): void
    {
        $userId = (int) auth()->id();
        $user = auth()->user();

        event(new CommentCreated($post->id, $commentData, $post->user_id, $userId));

        if ($post->user_id !== $userId) {
            $userName = $user ? $user->name : 'Someone';
            event(new NewActivityAlert(
                $post->user_id,
                $userName.' commented on your post.'
            ));
        }
    }
}
