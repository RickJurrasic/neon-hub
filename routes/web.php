<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CoreEngineController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NeonHubController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Route;

// Veřejná hlavní stránka
Route::get('/', [NeonHubController::class, 'index'])->name('neon.hub');

// Zabezpečené trasy pro přihlášené uživatele
Route::middleware('auth')->group(function (): void {
    // Profil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Posts & Comments
    Route::post('/posts/{post}/like', [LikeController::class, 'store'])->name('posts.like');
    Route::delete('/posts/{post}/like', [LikeController::class, 'destroy'])->name('posts.unlike');
    Route::post('/posts/{post}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');

    // Friendships (Přesunuto do auth skupiny)
    Route::patch('/friendships/{id}', [FriendshipController::class, 'update'])->name('friendships.update');
    Route::delete('/friendships/{id}', [FriendshipController::class, 'destroy'])->name('friendships.destroy');

    // Messages & Telemetry
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/conversations/{id}', [MessageController::class, 'destroy'])->name('conversations.destroy');
    Route::get('/api/core-engine/telemetry', [CoreEngineController::class, 'getTelemetry'])->name('telemetry');

    // System Initialization (Nyní odkazuje na čistý Controller)
    Route::post('/system/initialize-node', [SystemController::class, 'initializeNode'])->name('system.initialize');
});

// Dashboard
Route::get('/dashboard', fn() => inertia('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');