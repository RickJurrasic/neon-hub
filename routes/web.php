<?php

use App\Events\SystemAlertTriggered;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NeonHubController;
use App\Http\Controllers\ProfileController;
use App\Jobs\AutoSendAgentMessage;
use App\Jobs\SendFriendRequest;
use App\Jobs\SendMessage;
use Illuminate\Support\Facades\Route;

Route::get('/', [NeonHubController::class, 'index'])->name('neon.hub');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Inicializace uzlu s automatickým Sentinel pozdravem
    Route::post('/system/initialize-node', function () {
        $userId = auth()->id();

        // 1. Pošle žádost o přátelství od Sentinela
        SendFriendRequest::dispatch($userId, 'SENTINEL_01')
            ->delay(now()->addSeconds(4));

        // 2. Pošle automatickou uvítací zprávu od Sentinela
        AutoSendAgentMessage::dispatch($userId, 'SENTINEL_01')
            ->delay(now()->addSeconds(7));

        return response()->json(['status' => 'NODE_INITIALIZED']);
    })->name('system.initialize');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::delete('/messages/{id}', [MessageController::class, 'destroy'])->name('messages.destroy');
});

Route::get('/test-signal', function () {
    event(new SystemAlertTriggered(auth()->id(), 'Krizový stav aktivován!'));

    return 'Signál odpálen!';
});

Route::get('/test-message', function () {
    SendMessage::dispatch(auth()->id(), [
        'sender' => 'CYBER_OPERATOR',
        'text' => 'Připojení k uzlu navázáno.',
    ])->delay(now()->addSeconds(1));

    return 'Zpráva naplánována za 1 sekundu.';
});

Route::get('/dashboard', function () {
    return inertia('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::patch('/friendships/{id}', [FriendshipController::class, 'update'])->name('friendships.update');
Route::delete('/friendships/{id}', [FriendshipController::class, 'destroy']);

require __DIR__.'/auth.php';
