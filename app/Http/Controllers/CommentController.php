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

            'author' => auth()->user()->name,

            'timestamp' => $comment->created_at->format('H:i'),

            'created_at' => $comment->created_at->toIso8601String(),

        ];

        // Real-time broadcast pro ostatní uživatele (aktualizace feedu)
        event(new CommentCreated($post->id, $commentData, $post->user_id, auth()->id()));

        // Notifikace autora postu (pokud to není jeho vlastní komentář)

        if ($post->user_id !== auth()->id()) {

            event(new NewActivityAlert(

                $post->user_id,

                auth()->user()->name.' commented on your post.'

            ));

        }

        // Vrátíme data pro okamžité zobrazení odesílateli

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
