<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Services\LikeService;
use Illuminate\Http\JsonResponse;

class LikeController extends Controller
{
    public function __construct(
        protected LikeService $likeService
    ) {}

    public function store(Post $post): JsonResponse
    {
        $result = $this->likeService->like($post);

        return response()->json($result);
    }

    public function destroy(Post $post): JsonResponse
    {
        $result = $this->likeService->unlike($post);

        return response()->json($result);
    }
}
