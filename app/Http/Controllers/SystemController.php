<?php

namespace App\Http\Controllers;

use App\Actions\SendFriendRequestAction;
use App\Jobs\HandleAgentResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemController extends Controller
{
    public function initializeNode(): JsonResponse
    {
        try {
            $rawUserId = auth()->id();

            if (! $rawUserId) {
                return response()->json(['error' => 'Uživatel není přihlášen.'], 401);
            }

            $userId = (int) $rawUserId;

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

            HandleAgentResponse::dispatch($userId, null, 'SENTINEL_01')
                ->delay(now()->addSeconds(7));

            return response()->json(['status' => 'NODE_INITIALIZED']);
        } catch (Throwable $e) {
            Log::error('Inicializace uzlu selhala: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'ERROR',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}