<?php

namespace App\Http\Controllers;

use App\Jobs\HandleAgentResponse;
use App\Models\Friendship;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class NeonHubController extends Controller
{
    public function index()
    {
        $authId = auth()->id();

        $props = [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
            'initialState' => $authId ? $this->getInitialState($authId) : null,
        ];

        if ($authId) {
            HandleAgentResponse::dispatch($authId)->delay(now()->addSeconds(7));
        }

        return Inertia::render('Welcome', $props);
    }

    private function getInitialState(int $authId): array
    {
        return [
            'friendships' => $this->getFriendshipData($authId),
            'messages' => [],
            'posts' => $this->getPostsData($authId),
        ];
    }

    private function getPostsData(int $authId): array
    {
        return Post::with(['author', 'comments.author'])
            ->withCount('likes')
            ->withExists([
                'likes as is_liked' => function ($query) use ($authId) {
                    $query->where(function ($q) use ($authId) {
                        $q->where('where_id', $authId)->orWhere('user_id', $authId);
                    });
                },
            ])
            ->latest()
            ->get()
            ->map(fn ($post) => $this->transformPost($post, $authId))
            ->toArray();
    }

    private function transformPost($post, int $authId): array
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

    private function transformComments($comments, int $authId): array
    {
        return $comments->map(fn ($comment) => [
            'id' => $comment->id,
            'author' => $comment->author->name ?? 'ANONYMOUS',
            'text' => $comment->content,
            'timestamp' => $comment->created_at->format('H:i'),
            'can_edit' => $comment->user_id === $authId,
        ])->toArray();
    }

    private function getFriendshipData($authId): array
    {
        return [
            'requests' => $this->getPendingRequests($authId),
            'active' => $this->getActiveFriendships($authId),
        ];
    }

    private function getPendingRequests($authId): array
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

    private function getActiveFriendships($authId): array
    {
        return Friendship::where(function ($q) use ($authId) {
            $q->where('sender_id', $authId)->orWhere('recipient_id', $authId);
        })
            ->where('status', 'accepted')
            ->get()
            ->map(fn ($friendship) => $this->transformActiveFriendship($friendship, $authId))
            ->filter()
            ->values()
            ->toArray();
    }

    private function transformActiveFriendship($friendship, $authId): ?array
    {
        $friendId = $friendship->sender_id === $authId ? $friendship->recipient_id : $friendship->sender_id;
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
