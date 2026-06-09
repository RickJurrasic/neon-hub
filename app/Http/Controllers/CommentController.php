<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Events\NewActivityAlert;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Uloží nový komentář k příspěvku a odešle real-time broadcast.
     */
    public function store(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment = $post->comments()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        $commentData = [
            'id' => $comment->id,
            'post_id' => $post->id,
            'content' => $comment->content,
            'author' => [
                'name' => auth()->user()->name,
            ],
            'created_at' => $comment->created_at->toIso8601String(),
        ];

        // Odpálíme real-time synchronizaci pro ostatní připojené uzly
        broadcast(new CommentCreated($post->id, $commentData))->toOthers();
        event(new NewActivityAlert($post->user_id, 'Někdo komentoval tvůj post.'));

        // Vrátíme přímou JSON odpověď odesílateli pro okamžitý zápis do Pinie
        return response()->json($commentData);
    }

    /**
     * Aktualizuje stávající komentář.
     */
    public function update(Request $request, Comment $comment)
    {
        if ($comment->user_id !== auth()->id()) {
            return response()->json(['error' => 'ACCESS_DENIED'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $comment->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'id' => $comment->id,
            'content' => $comment->content,
            'status' => 'NODE_UPDATED',
        ]);
    }
}
