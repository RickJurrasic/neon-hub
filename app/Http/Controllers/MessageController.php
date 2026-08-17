<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteConversationRequest;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Services\LlmRateLimiter;
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

        public function store(StoreMessageRequest $request, LlmRateLimiter $limiter): MessageResource|JsonResponse
    {
        // AUTHORITY GATE — INTERACTIVE bucket (server-side, atomic via cache store).
        // POST /messages is the sole human -> agent reply entry point, so this is
        // the single consume() per turn. HandleAgentResponse (dispatched
        // downstream inside MessageService::storeMessage) must NOT reconsume,
        // otherwise the interactive budget double-counts. Returning 429 BEFORE
        // storeMessage guarantees no DB writes occur on a rate-limited request.
        $user = $request->user();

        if ($user && ! $limiter->consume(LlmRateLimiter::INTERACTIVE, $user)) {
            $retryAfter = $limiter->retryAfter(LlmRateLimiter::INTERACTIVE, $user);

            return response()->json(
                ['message' => 'AI_RATE_LIMITED'],
                429,
                [
                    'Retry-After'         => $retryAfter,
                    'X-RateLimit-Limit'   => $limiter->max(LlmRateLimiter::INTERACTIVE, $user),
                    'X-RateLimit-Remaining' => max(0, $limiter->max(LlmRateLimiter::INTERACTIVE, $user) - $limiter->attempts(LlmRateLimiter::INTERACTIVE, $user)),
                    'X-RateLimit-Reset'   => now()->addSeconds($retryAfter)->timestamp,
                ]
            );
        }

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

    public function destroy(DeleteConversationRequest $request, string $conversationId): JsonResponse
    {
        // Authorization (owner-only) is enforced by DeleteConversationRequest::authorize(); conversation id is a string UUID, no int cast.
        $this->messageService->destroyConversation($conversationId);

        return response()->json(['status' => 'NODE_PURGED']);
    }
}
