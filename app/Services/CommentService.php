<?php

namespace App\Services;

use App\Events\CommentCreated;
use App\Events\NewActivityAlert;
use App\Models\Comment;
use App\Models\Post;

class CommentService
{
    public function createComment(Post $post, array $data): array
    {
        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $data['content'],
        ]);

        $commentData = $this->formatCommentData($comment, $post);

        $this->notifyAndBroadcast($post, $commentData);

        return $commentData;
    }

    public function updateComment(Comment $comment, array $data): Comment
    {
        $comment->update([
            'content' => $data['content'],
        ]);

        return $comment;
    }

    private function formatCommentData(Comment $comment, Post $post): array
    {
        return [
            'id' => $comment->id,
            'post_id' => $post->id,
            'content' => $comment->content,
            'author' => auth()->user()->name,
            'timestamp' => $comment->created_at->format('H:i'),
            'created_at' => $comment->created_at->toIso8601String(),
        ];
    }

    private function notifyAndBroadcast(Post $post, array $commentData): void
    {
        event(new CommentCreated($post->id, $commentData, $post->user_id, auth()->id()));

        if ($post->user_id !== auth()->id()) {
            event(new NewActivityAlert(
                $post->user_id,
                auth()->user()->name.' commented on your post.'
            ));
        }
    }
}