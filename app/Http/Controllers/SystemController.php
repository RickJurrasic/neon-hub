<?php

namespace App\Http\Controllers;

use App\Actions\SendFriendRequestAction;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use App\Services\LlmRateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemController extends Controller
{
    public function initializeNode(LlmRateLimiter $limiter): JsonResponse
    {
        try {
            $rawUserId = auth()->id();

            if (! $rawUserId) {
                return response()->json(['error' => 'Uživatel není přihlášen.'], 401);
            }

            $userId = (int) $rawUserId;
            $user = auth()->user();

            if (! $user) {
                return response()->json(['error' => 'Uživatel není přihlášen.'], 401);
            }

            // Enter-System-specific budget. SEPARATE from `startup` (page-load
            // greeting) and `interactive` (POST /messages), so repeated Enter
            // presses cannot re-trigger the welcome friend-request + bot message
            // + feed-post sequence. Keyed per-user/per-demo-session → demo
            // sessions stay isolated. 429 is returned BEFORE any startup action
            // is dispatched (no side effects on a rejected request).
            if (! $limiter->consume(LlmRateLimiter::ENTER_SYSTEM, $user)) {
                return response()->json(
                    ['message' => 'AI_RATE_LIMITED'],
                    429,
                    ['Retry-After' => $limiter->retryAfter(LlmRateLimiter::ENTER_SYSTEM, $user)],
                );
            }

            dispatch(function () use ($userId): void {
                $sentinel = User::where('name', 'like', '%Sentinel%')
                    ->orWhere('handle', 'like', '%sentinel%')
                    ->first();

                if ($sentinel) {
                    app(SendFriendRequestAction::class)->execute((int) $sentinel->id, $userId);
                } else {
                    Log::warning('Sentinel bot nebyl v databázi nalezen pro inicializaci.');
                }
            })->delay(now()->addSeconds(4));

            HandleAgentResponse::dispatch($userId, null, 'SENTINEL_01', true)
                ->delay(now()->addSeconds(7));

            return response()->json(['status' => 'NODE_INITIALIZED']);
        } catch (Throwable $e) {
            // Full exception is logged server-side for operators/debugging;
            // the HTTP response intentionally carries only a generic, fixed
            // error code so internal messages (file paths, SQL fragments, API
            // keys, etc.) are never leaked to the client.
            Log::error('Inicializace uzlu selhala: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'ERROR',
                'message' => 'NODE_INIT_FAILED',
            ], 500);
        }
    }
}
