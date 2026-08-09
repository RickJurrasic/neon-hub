<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NeonHubService
{
    /**
     * @return array<string, mixed>
     */
    public function getInitialState(int $authId): array
    {
        return [
            'friendships' => $this->getFriendshipData($authId),
            'messages' => [],
            'posts' => $this->getPostsData($authId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPostsData(int $authId): array
    {
        return Post::with(['author', 'comments.author'])
            ->withCount('likes')
            ->withExists([
                'likes as is_liked' => function ($query) use ($authId): void {
                    $query->where(function ($q) use ($authId): void {
                        $q->where('where_id', $authId)->orWhere('user_id', $authId);
                    });
                },
            ])
            ->latest()
            ->get()
            ->map(function ($post) use ($authId) {
                /** @var Post $post */
                return $this->transformPost($post, $authId);
            })
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function transformPost(Post $post, int $authId): array
    {
        return [
            'id' => $post->id,
            'author' => $post->author->name ?? 'UNKNOWN_NODE',
            'content' => $post->content,
            'type' => $post->type,
            'time' => $post->latency ?? '0.0ms',
            'likes_count' => $post->likes_count ?? 0,
            'is_liked' => (bool) $post->is_liked,
            'comments_count' => $post->comments->count(),
            'image' => $post->image_url,
            'image_meta' => $post->image_meta,
            'comments' => $this->transformComments($post->comments, $authId),
        ];
    }

    /**
     * @param Collection<int, Comment> $comments
     * @return array<int, array<string, mixed>>
     */
    private function transformComments(Collection $comments, int $authId): array
    {
        return $comments->map(fn ($comment) => [
            'id' => $comment->id,
            'author' => $comment->author->name ?? 'ANONYMOUS',
            'text' => $comment->content,
            'timestamp' => $comment->created_at?->format('H:i') ?? '00:00',
            'can_edit' => $comment->user_id === $authId,
        ])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    private function getFriendshipData(int $authId): array
    {
        return [
            'requests' => $this->getPendingRequests($authId),
            'active' => $this->getActiveFriendships($authId),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendingRequests(int $authId): array
    {
        return Friendship::where('recipient_id', $authId)
            ->where('status', 'pending')
            ->join('users', 'friendships.sender_id', '=', 'users.id')
            ->select([
                'friendships.id', 'users.id as user_id', 'users.name', 'users.role',
                'users.bio', 'users.trust_level', 'users.latency', 'users.avatar_url as avatar',
                'friendships.status',
            ])
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getActiveFriendships(int $authId): array
    {
        return Friendship::where(function ($q) use ($authId): void {
            $q->where('sender_id', $authId)->orWhere('recipient_id', $authId);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(fn ($friendship) => $this->transformActiveFriendship($friendship, $authId))
            ->filter()
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function transformActiveFriendship(Friendship $friendship, int $authId): ?array
    {
        $friendId = $friendship->sender_id === $authId ? $friendship->recipient_id : $friendship->sender_id;
        
        /** @var User|null $friend */
        $friend = User::find($friendId);

        if (! $friend) {
            return null;
        }

        return [
            'id' => $friendship->id,
            'user_id' => $friend->id,
            'name' => $friend->name,
            'role' => $friend->role ?? 'EXTERNAL_NODE',
            'bio' => $friend->bio ?? '"Šifrované bio prázdné."',
            'trust_level' => $friend->trust_level ?? 50,
            'latency' => $friend->latency ?? '24ms_STABLE',
            'avatar' => $friend->avatar_url,
            'status' => 'accepted',
        ];
    }
}