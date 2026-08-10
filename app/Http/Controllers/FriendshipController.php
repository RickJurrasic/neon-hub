<?php

namespace App\Http\Controllers;

use App\Actions\SendFriendRequestAction;
use App\Http\Requests\StoreFriendshipRequest;
use App\Models\Friendship;
use App\Models\User;
use App\Services\FriendshipService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class FriendshipController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected FriendshipService $friendshipService
    ) {}

    public function store(StoreFriendshipRequest $request, SendFriendRequestAction $action): JsonResponse
    {
        $recipientId = (int) $request->validated('recipient_id');
        $senderId = (int) auth()->id();

        $friendship = $action->execute($senderId, $recipientId);

        if (! $friendship) {
            return response()->json(['status' => 'error', 'message' => 'Žádost již existuje.'], 422);
        }

        /** @var User|null $recipient */
        $recipient = User::find($recipientId);

        if (! $recipient) {
            return response()->json(['status' => 'error', 'message' => 'Uživatel nenalezen.'], 404);
        }

        return response()->json([
            'status' => 'Request sent',
            'friendship' => $this->friendshipService->formatFriendResponse($friendship->id, $recipient, 'pending'),
        ]);
    }

    public function update(Friendship $friendship): JsonResponse
    {
        $this->authorize('update', $friendship);

        $result = $this->friendshipService->acceptFriendship($friendship);

        return response()->json($result);
    }

    public function destroy(Friendship $friendship): JsonResponse
    {
        $this->authorize('delete', $friendship);

        $result = $this->friendshipService->deleteFriendship($friendship);

        return response()->json($result);
    }
}
