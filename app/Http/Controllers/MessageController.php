<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(
        protected MessageService $messageService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $messages = $this->messageService->getIndexMessages();

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request): MessageResource|JsonResponse
    {
        $messageData = $this->messageService->storeMessage(
            $request->validated('message_id'),
            $request->validated('text')
        );

        if (! $messageData) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        return new MessageResource((object) $messageData);
    }

    public function destroy($conversationId): JsonResponse
    {
        $deleted = $this->messageService->destroyConversation($conversationId);

        if (! $deleted) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        return response()->json(['status' => 'NODE_PURGED']);
    }
}