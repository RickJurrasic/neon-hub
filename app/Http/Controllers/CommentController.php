<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    public function __construct(
        protected CommentService $commentService
    ) {}

    /**
     * Uloží nový komentář k příspěvku a odešle real-time broadcast.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $commentData = $this->commentService->createComment($post, $request->validated());

        return response()->json($commentData);
    }

    /**
     * Aktualizuje stávající komentář.
     */
    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        $updatedComment = $this->commentService->updateComment($comment, $request->validated());

        return response()->json([
            'id' => $updatedComment->id,
            'content' => $updatedComment->content,
            'status' => 'NODE_UPDATED',
        ]);
    }
}