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
        // auth()->id() může vrátit int|string|null, služba chce striktně int (nebo si to vynutíme přetypováním)
        $userId = (int) auth()->id();
        $messages = $this->messageService->getIndexMessages($userId);

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request): MessageResource|JsonResponse
    {
        // Služba podle hlášky očekává na 1. pozici string ($messageId), ale předávalo se int.
        // Pokud má být messageId string, přetypujeme ho na (string). (Nebo pokud má být int, upravuje se služba – tady předpokládáme string podle chybové hlášky).
        $messageData = $this->messageService->storeMessage(
            (string) $request->validated('message_id'),
            (string) $request->validated('text')
        );

        if (! $messageData) {
            return response()->json(['error' => 'NODE_NOT_FOUND'], 404);
        }

        return new MessageResource((object) $messageData);
    }

    public function destroy(int|string $conversationId): JsonResponse
    {
        // Metoda akceptuje int|string, ale služba chce striktně int. Převedeme to bezpečně na int.
        $this->messageService->destroyConversation((int) $conversationId);

        return response()->json(['status' => 'NODE_PURGED']);
    }
}
