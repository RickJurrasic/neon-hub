<?php

use App\Actions\SendFriendRequestAction;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CoreEngineController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NeonHubController;
use App\Http\Controllers\ProfileController;
use App\Jobs\HandleAgentResponse;
use App\Jobs\SendFriendRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', [NeonHubController::class, 'index'])->name('neon.hub');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/posts/{post}/like', [LikeController::class, 'store'])->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])->name('posts.unlike');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    // NOVÁ ROUTA PRO UPDATE:
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');

    Route::post('/system/initialize-node', function () {
        try {
            $userId = auth()->id();

            // Bezpečnostní pojistka, pokud by session vypršela
            if (! $userId) {
                return response()->json(['error' => 'Uživatel není přihlášen.'], 401);
            }

            // 1. FIX: Použití anonymního jobu, který nepotřebuje chybějící soubor SendFriendRequest.php
            dispatch(function () use ($userId) {
                // Najdeme Sentinela v databázi (podle jména nebo handle)
                $sentinel = User::where('name', 'like', '%Sentinel%')
                    ->orWhere('handle', 'like', '%sentinel%')
                    ->first();

                if ($sentinel) {
                    // Spustíme tvou existující akci pro poslání žádosti o přátelství
                    app(SendFriendRequestAction::class)->execute((int) $sentinel->id, (int) $userId);
                } else {
                    Log::warning('Sentinel bot nebyl v databázi nalezen pro inicializaci.');
                }
            })->delay(now()->addSeconds(4));

            // 2. Úvodní pozdrav (Tento job existuje a je sjednocený)
            HandleAgentResponse::dispatch($userId, null, 'SENTINEL_01')
                ->delay(now()->addSeconds(7));

            return response()->json(['status' => 'NODE_INITIALIZED']);

        } catch (Throwable $e) {
            // Zaloguje detail do laravel.log
            Log::error('Inicializace uzlu selhala: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            // Vrátí detail chyby přímo do Axia, abys ho viděl v prohlížeči
            return response()->json([
                'status' => 'ERROR',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    })->name('system.initialize');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/conversations/{id}', [MessageController::class, 'destroy']);
    Route::get('/api/core-engine/telemetry', [CoreEngineController::class, 'getTelemetry']);
});

Route::get('/dashboard', function () {
    return inertia('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::patch('/friendships/{id}', [FriendshipController::class, 'update'])->name('friendships.update');
Route::delete('/friendships/{id}', [FriendshipController::class, 'destroy']);

require __DIR__.'/auth.php';
